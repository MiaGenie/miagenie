<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrePostResource extends JsonResource
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
            'draft_id' => $this->draft_id,
            'caption' => $this->caption,
            'status' => $this->status,
        ];
    }
}
