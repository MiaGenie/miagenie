<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ThreadResource extends JsonResource
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
            'rule_id' => $this->rule_id,
            'thread_provider_id' => $this->thread_provider_id,
        ];

    }
}
