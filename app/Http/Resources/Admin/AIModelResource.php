<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AIModelResource extends JsonResource
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
            'model' => $this->model,
            'json_schema' => $this->json_schema,
            'temperature_top_p' => $this->temperature_top_p,
            'file_search' => $this->file_search,
            'reasoning_effort' => $this->reasoning_effort,
        ];

    }
}
