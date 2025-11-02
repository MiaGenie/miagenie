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
            'instructions' => $this->instructions,
            'ai_model' => $this->ai_model,
            'response_format' => $this->response_format,
            'json_schema' => $this->json_schema,
            'temperature' => $this->temperature,
            'top_p' => $this->top_p,
            'reasoning_effort' => $this->reasoning_effort,
            'vector_id' => $this->vector_id,
            'link_upstream' => $this->link_upstream,
            'message' => $this->message,
            'output' => $this->output,
            'requires_review' => $this->requires_review,
            'review_message_user' => $this->review_message_user,
            'review_message_system' => $this->review_message_system,
            'optional' => $this->optional,
            'depends_on_field' => $this->depends_on_field,
            'depends_on_option' => $this->depends_on_option,
            'position' => $this->position,
        ];

    }
}
