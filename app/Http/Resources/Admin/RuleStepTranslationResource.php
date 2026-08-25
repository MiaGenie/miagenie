<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class RuleStepTranslationResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    public function toArray($request): array
    {

        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'instructions' => $this->instructions,
            'response_format' => $this->response_format,
            'message' => $this->message,
            'requires_review' => $this->requires_review,
            'review_message_user' => $this->review_message_user,
            'review_message_system' => $this->review_message_system,
        ];

    }
}
