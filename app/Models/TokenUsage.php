<?php

namespace App\Models;

use App\Enums\Modality;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\Data\Usage;

/**
 * The tokens one model call cost a workspace.
 *
 * Rows are written only by App\Listeners\RecordTokenUsage, from the SDK's events, and summed into
 * the week / month / total counters shown on the panel workspace view page and the dashboard.
 */
class TokenUsage extends Model
{
    use OwnedByWorkspace;

    protected $table = 'genie_token_usages';

    protected $fillable = [
        'workspace_id',
        'modality',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
    ];

    protected $casts = [
        'modality' => Modality::class,
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
    ];

    /**
     * Reduce the SDK's usage to what the call cost on each side.
     *
     * Every gateway subtracts cached input from `promptTokens`, so it is added back. Reasoning is
     * already part of `completionTokens` everywhere except Gemini, which reports its thinking
     * tokens next to the answer's.
     *
     * @return array{input_tokens: int, output_tokens: int}
     */
    public static function countsFrom(Usage $usage, ?string $provider): array
    {
        $reasoningIsSeparate = $provider === Lab::Gemini->value;

        return [
            'input_tokens' => $usage->promptTokens + $usage->cacheReadInputTokens + $usage->cacheWriteInputTokens,
            'output_tokens' => $usage->completionTokens + ($reasoningIsSeparate ? $usage->reasoningTokens : 0),
        ];
    }

    /**
     * The workspace's tokens this calendar week (from Monday), this calendar month and overall.
     *
     * @return array{weekly: int, monthly: int, total: int}
     */
    public static function totalsFor(Workspace $workspace): array
    {
        $now = now();
        $tokens = 'input_tokens + output_tokens';

        $totals = static::byWorkspace($workspace)
            ->selectRaw("sum(case when created_at >= ? then {$tokens} else 0 end) as weekly", [$now->copy()->startOfWeek(CarbonInterface::MONDAY)])
            ->selectRaw("sum(case when created_at >= ? then {$tokens} else 0 end) as monthly", [$now->copy()->startOfMonth()])
            ->selectRaw("sum({$tokens}) as total")
            ->toBase()
            ->first();

        return [
            'weekly' => (int) ($totals->weekly ?? 0),
            'monthly' => (int) ($totals->monthly ?? 0),
            'total' => (int) ($totals->total ?? 0),
        ];
    }
}
