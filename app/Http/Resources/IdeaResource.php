<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IdeaResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {

        return [
            'id' => $this->uuid,
            'theme' => $this->theme,
            'description' => $this->description,
            'status' => $this->status,
            'source' => $this->source,
            'run_response_id' => $this->run_response_id,
            'funnel_stage' => $this->funnel_stage,
            'content_pillar' => $this->content_pillar,
            'drafts' => $this->drafts
        ];

    }
}
