<?php

namespace App\Http\Requests\Admin;

use App\Enums\VersionStatus;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;

class CloneVersion extends FormRequest
{
    public function handle(): Version
    {
        $version = Version::firstOrFailByUuid($this->route('version'));

        $newVersion = $version->replicate(['uuid']);
        $newVersion->name .= ' (Cloned)';
        $newVersion->status = VersionStatus::DISABLED;
        $newVersion->is_default = false;

        $newVersion->save();

        foreach ($version->fields as $field) {
            $newField = $field->replicate(['uuid']);
            $newField->version_id = $newVersion->id;
            $newField->save();
            foreach ($field->options as $option) {
                $newOption = $option->replicate(['uuid']);
                $newOption->field_id = $newField->id;
                $newOption->save();
            }
            foreach ($field->subFields as $subField) {
                $newSubField = $subField->replicate(['uuid']);
                $newSubField->field_id = $newField->id;
                $newSubField->save();
            }
        }

        foreach ($version->rules as $rule) {
            $newRule = $rule->replicate(['uuid']);
            $newRule->version_id = $newVersion->id;
            $newRule->save();
            foreach ($rule->steps as $step) {
                $newStep = $step->replicate(['uuid']);
                $newStep->rule_id = $newRule->id;
                $newStep->save();
            }
        }

        return $newVersion;
    }
}
