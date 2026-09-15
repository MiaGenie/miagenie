<?php

namespace App\Http\Resources\Admin;

use App\Models\AiRunStepFile;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One slot version of an image step, for the admin run step page.
 *
 * @mixin AiRunStepFile
 */
class AiRunStepImageResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'brief' => $this->brief,
            'prompt' => $this->prompt,
            'feedback' => $this->feedback,
            'mode' => $this->mode,
            'superseded_at' => $this->superseded_at?->format('Y-m-d H:i:s'),
            'revising' => $this->isRevising(),
            'revision_error' => $this->revision_error,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'file' => $this->file ? [
                'id' => $this->file->uuid,
                'name' => $this->file->name,
                'url' => $this->file->getUrl(),
                'mime_type' => $this->file->mime_type,
                'size' => $this->file->size,
            ] : null,
            'parent_file_id' => $this->parent?->uuid,
        ];
    }
}
