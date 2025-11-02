<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class RuleResource extends JsonResource
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
            'rule_type' => $this->rule_type,
            'link_upstream' => $this->link_upstream,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ];

    }
}
