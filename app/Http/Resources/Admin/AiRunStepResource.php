<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AiRunStepResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'run_id' => $this->run_id,
            'step_id' => $this->step_id,
            'position' => $this->position,
            'modality' => $this->modality,
            'status' => $this->status,
            'output' => $this->output,
            'error' => $this->error,
            'error_details' => $this->error_details,
            'invocation_id' => $this->invocation_id,
            'message_id' => $this->message_id,
            'duration' => $this->duration,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
