<?php

namespace App\Http\Requests\Workspace\Idea;

use App\Enums\FunnelStage;
use App\Enums\PostFormat;
use App\Enums\StrategyStatus;
use App\Genie\Ideas\ContentPillars;
use App\Genie\Ideas\IdeasRequest;
use App\Models\Strategy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * What the ideas page asks a run for: a format or any, a pillar or all, a stage or any, and how
 * many ideas in total.
 */
class GenerateIdeas extends FormRequest
{
    protected ?Strategy $strategy = null;

    protected bool $strategyResolved = false;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'post_format' => ['nullable', ValidationRule::enum(PostFormat::class)],
            'content_pillar' => ['nullable', 'string', ValidationRule::in(ContentPillars::titles($this->strategy()))],
            'funnel_stage' => ['required', ValidationRule::enum(FunnelStage::class)],
            'count' => ['required', 'integer', ValidationRule::in(IdeasRequest::COUNTS)],
        ];
    }

    /**
     * The strategy ideas are generated from: the workspace's newest, and only once it is approved.
     */
    public function strategy(): ?Strategy
    {
        if (! $this->strategyResolved) {
            $latest = Strategy::latest()->first();

            $this->strategy = $latest?->status === StrategyStatus::APPROVED ? $latest : null;
            $this->strategyResolved = true;
        }

        return $this->strategy;
    }

    public function toRequest(): IdeasRequest
    {
        return IdeasRequest::fromArray([
            'post_format' => $this->input('post_format'),
            'content_pillar' => $this->input('content_pillar'),
            'funnel_stage' => $this->input('funnel_stage'),
            'count' => $this->input('count'),
        ]);
    }
}
