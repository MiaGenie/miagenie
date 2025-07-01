<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class RunResponsesResource extends JsonResource
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
            'uuid' => $this->uuid,
            'thread_id' => $this->thread_id,
            'step_id' => $this->step_id,
            'status' => $this->status,
            'status_provider' => $this->status_provider,
            'message' => json_decode($this->message ?? ''),
        ];

    }
}
