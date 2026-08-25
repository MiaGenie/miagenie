<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class RuleStepResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    public function toArray($request): array
    {

        return [
            'id' => $this->uuid,
            'rule_id' => $this->rule_id,
            'rule_sub_type' => $this->rule_sub_type,
            'name' => $this->name,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'model_profile_id' => $this->model_profile_id,
            'response_format' => $this->response_format,
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
