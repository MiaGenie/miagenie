<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ModelProfileResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'model_profile_id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'model_tier' => $this->model_tier,
            'model' => $this->model,
            'timeout' => $this->timeout,
            'position' => $this->position,
        ];
    }
}
