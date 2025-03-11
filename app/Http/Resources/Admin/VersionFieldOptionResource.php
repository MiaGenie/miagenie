<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class VersionFieldOptionResource extends JsonResource
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
            'field_id' => $this->field_id,
            'name' => $this->name,
            'code_name' => $this->code_name,
            'checked' => $this->checked,
            'group' => $this->group,
            'position' => $this->position,
        ];

    }
}
