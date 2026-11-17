<?php

namespace App\Http\Requests\Workspace\Idea;

use App\Enums\FunnelStage;
use App\Enums\PostFormat;
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
     * The strategy ideas are generated from: the workspace's newest approved one. A newer strategy
     * an admin asked to regenerate takes over only once it is approved in turn.
     */
    public function strategy(): ?Strategy
    {
        if (! $this->strategyResolved) {
            $this->strategy = Strategy::approved()->latest('id')->first();
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
