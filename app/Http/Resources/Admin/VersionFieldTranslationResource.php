<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class VersionFieldTranslationResource extends JsonResource
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
            'sub_description' => $this->sub_description,
            'field_type' => $this->field_type
        ];

    }


}
