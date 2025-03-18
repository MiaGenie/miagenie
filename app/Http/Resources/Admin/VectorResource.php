<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class VectorResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'files' => $this->files,
            'vector_type' => $this->vector_type,
        ];

    }
}
