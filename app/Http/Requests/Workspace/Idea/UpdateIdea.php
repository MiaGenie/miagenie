<?php

namespace App\Http\Requests\Workspace\Idea;

use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use App\Enums\PostFormat;
use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule as ValidationRule;

class UpdateIdea extends FormRequest
{
    public function rules(): array
    {
        return [
            'theme' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'suggested_content' => ['nullable', 'string', 'max:5000'],
            'multimedia_suggestion' => ['nullable', 'string', 'max:5000'],
            'production_instructions' => ['nullable', 'string', 'max:5000'],
            'status' => [ValidationRule::enum(IdeaStatus::class)],
            'source' => [ValidationRule::enum(IdeaSource::class)],
            'funnel_stage' => [ValidationRule::enum(FunnelStage::class), 'nullable'],
            'post_format' => [ValidationRule::enum(PostFormat::class), 'nullable'],
            'content_pillar' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function handle(): int
    {
        $record = Idea::firstOrFailByUuid($this->route('idea'));

        return $record->update([
            'theme' => $this->input('theme'),
            'objective' => $this->input('objective'),
            'suggested_content' => $this->input('suggested_content'),
            'multimedia_suggestion' => $this->input('multimedia_suggestion'),
            'production_instructions' => $this->input('production_instructions'),
            'status' => $this->input('status'),
            'source' => $this->input('source'),
            'funnel_stage' => $this->input('funnel_stage'),
            'post_format' => $this->input('post_format'),
            'content_pillar' => $this->input('content_pillar'),
        ]);
    }
}
