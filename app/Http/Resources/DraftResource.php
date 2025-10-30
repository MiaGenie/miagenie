<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DraftResource extends JsonResource
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
            'workspace_id' => $this->workspace_id,
            'idea_id' => $this->idea_id,
            'goal' => $this->goal,
            'caption' => $this->caption,
            'media' => $this->media,
            'status' => $this->status,
        ];
    }
}
