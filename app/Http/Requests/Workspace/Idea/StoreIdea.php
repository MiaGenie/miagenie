<?php

namespace App\Http\Requests\Workspace\Idea;

use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use App\Models\Idea;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIdea extends FormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'theme' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'status' => [ValidationRule::enum(IdeaStatus::class)],
            'source' => [ValidationRule::enum(IdeaSource::class)],
            'funnel_stage' => [ValidationRule::enum(FunnelStage::class), 'nullable'],
        ];
    }

    /**
     * @return Idea
     */
    public function handle(): Idea
    {
        return Idea::create([
            'theme' => $this->input('theme'),
            'description' => $this->input('description'),
            'status' => $this->input('status'),
            'source' => $this->input('source'),
            'run_response_id' => $this->input('run_response_id'),
            'funnel_stage' => $this->input('funnel_stage'),
            'content_pillar' => $this->input('content_pillar')
        ]);
    }
}
