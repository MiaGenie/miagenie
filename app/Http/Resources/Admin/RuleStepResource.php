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
            'name' => $this->name,
            'description' => $this->description,
            'assistant_id' => $this->assistant_id,
            'message' => $this->message,
            'output' => $this->output,
            'position' => $this->position,
        ];

    }
}
