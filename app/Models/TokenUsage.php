<?php

namespace App\Models;

use App\Enums\Modality;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Inovector\Mixpost\Concerns\OwnedByWorkspace;
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
     * Since laravel/ai 1.0 the totals are the provider's complete counts on every gateway:
     * `inputTokens` includes cached and cache-written input, `outputTokens` includes reasoning
     * (Gemini's thinking tokens too), so they are taken as they are. The breakdown a TextUsage
     * carries (cache read / write, reasoning) is already inside them and is not added again.
     *
     * @return array{input_tokens: int, output_tokens: int}
     */
    public static function countsFrom(Usage $usage): array
    {
        return [
            'input_tokens' => $usage->inputTokens,
            'output_tokens' => $usage->outputTokens,
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
