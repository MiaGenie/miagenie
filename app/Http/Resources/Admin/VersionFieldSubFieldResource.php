<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class VersionFieldSubFieldResource extends JsonResource
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
            'sub_code_name' => $this->sub_code_name,
            'description' => $this->description,
            'type' => $this->type,
            'min_length' => $this->min_length,
            'max_length' => $this->max_length,
            'pattern' => $this->pattern,
            'min_items' => $this->min_items,
            'max_items' => $this->max_items,
            'required' => $this->required,
            'editable' => $this->editable,
            'enum_values' => $this->enum_values,
            'icon' => $this->icon,
            'class' => $this->class,
            'block' => $this->block,
            'position' => $this->position,
            'children' => $this->relationLoaded('childrenRecursive')
                ? self::collection($this->childrenRecursive)
                : [],
        ];
    }
}
