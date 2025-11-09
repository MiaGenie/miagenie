<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class VersionFieldResource extends JsonResource
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
            'version_id' => $this->version_id,
            'group_type' => $this->group_type,
            'name' => $this->name,
            'code_name' => $this->code_name,
            'description' => $this->description,
            'sub_description' => $this->sub_description,
            'field_type' => $this->field_type,
            'input_type' => $this->input_type,
            'file_type' => $this->file_type,
            'options' => VersionFieldOptionResource::collection($this->options),
            'min_length' => $this->min_length,
            'max_length' => $this->max_length,
            'min_value' => $this->min_value,
            'max_value' => $this->max_value,
            'step' => $this->step,
            'rows' => $this->rows,
            'is_multiple' => $this->is_multiple,
            'required' => $this->required,
            'genie_required' => $this->genie_required,
            'is_identifier' => $this->is_identifier,
            'hidden' => $this->hidden,
            'is_linkable' => $this->is_linkable,
            'display_title' => $this->display_title,
            'display_grouped' => $this->display_grouped,
            'display_field_title' => $this->display_field_title,
            'display_item_title' => $this->display_item_title,
            'display_faq_title' => $this->display_faq_title,
            'display_faq_text' => $this->display_faq_text,
            'position' => $this->position,
        ];

    }


}
