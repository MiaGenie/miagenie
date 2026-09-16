<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\ClonesSubFields;
use App\Models\Version;
use App\Models\VersionField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloneFieldInto extends FormRequest
{
    use ClonesSubFields;

    /**
     * Copy a field, with its options and sub-field tree, to the end of its group on another version.
     *
     * @throws ValidationException
     * @throws \Throwable
     */
    public function handle(): VersionField
    {
        $field = VersionField::with(['options', 'subFields'])
            ->where('uuid', $this->route('field'))
            ->firstOrFail();
        $target = Version::firstOrFailByUuid($this->route('target'));

        $targetGroup = VersionField::where('version_id', $target->id)
            ->where('group_type', $field->group_type);

        if ((clone $targetGroup)->where('code_name', $field->code_name)->exists()) {
            throw ValidationException::withMessages([
                'target' => __('genie.clone_field_into_code_name_exists'),
            ]);
        }

        $newField = $field->replicate(['uuid']);
        $newField->version_id = $target->id;
        $newField->position = (clone $targetGroup)->max('position') + 1;

        return DB::transaction(function () use ($field, $newField) {
            $newField->save();

            foreach ($field->options as $option) {
                $newOption = $option->replicate(['uuid']);
                $newOption->field_id = $newField->id;
                $newOption->save();
            }

            $this->cloneSubFields($field->subFields, $newField);

            return $newField;
        });
    }
}
