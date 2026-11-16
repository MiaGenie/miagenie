<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    public function toArray($request): array
    {

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'action' => $this->action,
            'run_step_id' => $this->run_step_id,
            'request' => $this->whenHas('request'),
            'response' => $this->whenHas('response'),
            'duration' => $this->duration,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at,
        ];

    }
}
