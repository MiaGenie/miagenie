<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class RuleStepResource extends JsonResource
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
            'rule_id' => $this->rule_id,
            'rule_sub_type' => $this->rule_sub_type,
            'name' => $this->name,
            'description' => $this->description,
            'assistant_id' => $this->assistant_id,
            'message' => $this->message,
            'output' => $this->output,
            'optional' => $this->optional,
            'position' => $this->position,
        ];

    }
}
