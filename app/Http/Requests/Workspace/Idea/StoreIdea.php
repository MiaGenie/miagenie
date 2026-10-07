<?php

namespace App\Http\Requests\Workspace\Idea;

use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use App\Enums\PostFormat;
use App\Models\Idea;
use App\Models\Strategy;
use App\Support\PublishTargets;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule as ValidationRule;
use Inovector\Mixpost\Facades\WorkspaceManager;

class StoreIdea extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string', 'max:5000'],
            'suggested_content' => ['nullable', 'string', 'max:5000'],
            'multimedia_suggestion' => ['nullable', 'string', 'max:5000'],
            'production_instructions' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['required', 'string', 'max:5000'],
            'publish_to' => ['nullable', 'array'],
            'publish_to.*' => ['required', 'string', ValidationRule::in($this->availableTargets())],
            'status' => [ValidationRule::enum(IdeaStatus::class)],
            'source' => [ValidationRule::enum(IdeaSource::class)],
            'funnel_stage' => [ValidationRule::enum(FunnelStage::class), 'nullable'],
            'post_format' => [ValidationRule::enum(PostFormat::class), 'nullable'],
            'content_pillar' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function handle(): Idea
    {
        return Idea::create([
            'strategy_id' => Strategy::latest()->first()?->id,
            'title' => $this->input('title'),
            'goal' => $this->input('goal'),
            'suggested_content' => $this->input('suggested_content'),
            'multimedia_suggestion' => $this->input('multimedia_suggestion'),
            'production_instructions' => $this->input('production_instructions'),
            'images' => $this->images(),
            'publish_to' => $this->publishTo(),
            'status' => $this->input('status'),
            'source' => $this->input('source'),
            'funnel_stage' => $this->input('funnel_stage'),
            'post_format' => $this->input('post_format'),
            'content_pillar' => $this->input('content_pillar'),
        ]);
    }

    /**
     * The image briefs as a list, or null when there are none: an empty list is nothing to keep.
     *
     * @return list<string>|null
     */
    protected function images(): ?array
    {
        $images = array_values((array) $this->input('images', []));

        return $images === [] ? null : $images;
    }

    /**
     * The providers to publish to as a list, or null when there are none.
     *
     * @return list<string>|null
     */
    protected function publishTo(): ?array
    {
        $providers = array_values(array_unique((array) $this->input('publish_to', [])));

        return $providers === [] ? null : $providers;
    }

    /**
     * The providers on offer for the submitted format: those with an account that accept it.
     *
     * @return list<string>
     */
    protected function availableTargets(): array
    {
        $format = PostFormat::tryFrom((int) $this->input('post_format'));

        return PublishTargets::forWorkspace(WorkspaceManager::current()->id)->available($format);
    }
}
