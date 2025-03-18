<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AssistantResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {

        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'assistant_type' => $this->assistant_type,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'model' => $this->model,
            'vector_id' => $this->vector_id,
            'response_format' => $this->response_format,
            'json_schema' => $this->json_schema,
            'temperature' => $this->temperature,
            'top_p' => $this->top_p,
        ];

    }
}
