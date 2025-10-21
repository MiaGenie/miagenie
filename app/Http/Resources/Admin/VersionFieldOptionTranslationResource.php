<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class VersionFieldOptionTranslationResource extends JsonResource
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
            'name' => $this->getTranslation('name', $request->route('locale')),
            'code_name' => $this->code_name,
        ];

    }
}
