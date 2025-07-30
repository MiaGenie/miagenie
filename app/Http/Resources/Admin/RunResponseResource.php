<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class RunResponseResource extends JsonResource
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
            'id' => $this->id,
            'uuid' => $this->uuid,
            'run_id' => $this->run_id,
            'step_id' => $this->step_id,
            'response_provider_id' => $this->response_provider_id,
            'status_provider' => $this->status_provider,
            'error' => $this->error,
            'error_details' => $this->error_details,
            'incomplete_details' => $this->incomplete_details,
            'output' => $this->output,
            'output_put' => $this->output_put,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];

    }
}
