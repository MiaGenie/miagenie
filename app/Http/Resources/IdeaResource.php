<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IdeaResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    public function toArray($request): array
    {

        return [
            'id' => $this->uuid,
            'theme' => $this->theme,
            'objective' => $this->objective,
            'suggested_content' => $this->suggested_content,
            'multimedia_suggestion' => $this->multimedia_suggestion,
            'production_instructions' => $this->production_instructions,
            'status' => $this->status,
            'source' => $this->source,
            'funnel_stage' => $this->funnel_stage,
            'post_format' => $this->post_format,
            'content_pillar' => $this->content_pillar,
            'created_at' => $this->created_at,
        ];

    }
}
