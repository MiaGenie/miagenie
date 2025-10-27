<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\IngestVersionFields;
use App\Constants\FormTypeDefaults;
use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Enums\VersionGroupType;
use App\Models\Version;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use App\Rules\Fields\FieldOptions;
use App\Rules\Fields\IsIdentifier;
use App\Rules\SnakeCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateVersionField extends FormRequest
{
    use IngestVersionFields;

    /**
     * @var Version
     */
    private Version $version;

    /**
     * @return array
     */
    public function rules(): array
    {
        $this->version ??= Version::firstOrFailByUuid($this->route('version'));

        return [
            'group_type' => ['required', Rule::enum(VersionGroupType::class)],
            'field_type' => ['required', Rule::enum(FormFieldType::class)],
            'input_type' => ['nullable', Rule::enum(FormInputType::class)],
            'name' => ['required', 'string', 'max:500'],
            'code_name' => [
                'required',
                new SnakeCase(),
                Rule::unique(VersionField::class, 'code_name')
                    ->where('group_type', $this->input('group_type'))
                    ->where('version_id', $this->version->id)
                    ->whereNot('uuid', $this->route('field'))
                , 'max:255'
            ],
            'options' => [new FieldOptions()],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.code_name' => ['required', 'distinct', 'max:255'],
            'rows' => ['nullable', 'integer', 'between:' . FormTypeDefaults::ROWS_MIN . ',' . FormTypeDefaults::ROWS_MAX],
            'min_length' => ['nullable', 'integer', $this->filled('max_height') ? 'lt:max_length' : '', 'max:' . $this->getMaxLength()],
            'max_length' => ['nullable', 'integer', $this->filled('min_length') ? 'gt:min_length' : '', 'max:' . $this->getMaxLength()],
            'min_value' => ['nullable', 'numeric', $this->filled('max_value') ? 'lt:max_value' : ''],
            'max_value' => ['nullable', 'numeric', $this->filled('min_value') ? 'gt:min_value' : '' ],
            'step' => ['nullable', 'numeric'],
            'is_identifier' => [
                new IsIdentifier(),
                Rule::unique(VersionField::class, 'is_identifier')
                    ->where('is_identifier', true)
                    ->where('group_type', $this->input('group_type'))
                    ->where('version_id', $this->version->id)
                    ->whereNot('uuid', $this->route('field'))
            ],
        ];
    }

    /**
     * @return int
     * @throws \Throwable
     */
    public function handle(): int
    {
        $this->version ??= Version::firstOrFailByUuid($this->route('version'));

        $record = VersionField::firstOrFailByUuid($this->route('field'));

        return DB::transaction(function () use ($record) {
            $update = $record->update([
                'group_type' => $this->input('group_type'),
                'name' => $this->input('name'),
                'code_name' => $this->input('code_name'),
                'description' => $this->input('description'),
                'sub_description' => $this->input('sub_description'),
                'field_type' => $this->input('field_type'),
                'input_type' => $this->input('input_type'),
                'min_length' => $this->input('min_length'),
                'max_length' => $this->input('max_length'),
                'min_value' => $this->input('min_value'),
                'max_value' => $this->input('max_value'),
                'step' => $this->input('step'),
                'rows' => $this->input('rows'),
                'is_multiple' => $this->input('is_multiple'),
                'required' => $this->input('required'),
                'genie_required' => $this->input('genie_required'),
                'is_identifier' => $this->input('is_identifier'),
                'hidden' => $this->input('hidden')
            ]);

            $this->handleChildren($record);

            return $update;
        });

    }

    /**
     * @param VersionField $record
     * @return void
     */
    public function handleChildren(VersionField $record): void
    {
        $this->deleteChildren($record);

        $this->updateChildren($record);
    }


    /**
     * @param VersionField $record
     * @return void
     */
    public function deleteChildren(VersionField $record): void
    {
        $updateUuids = array_column($this->input('options'), 'id');

        $deletedChildren = VersionFieldOption::where('field_id', $record->id)
            ->whereNotIn('uuid', $updateUuids);

        if ($deletedChildren->count()) {

            VersionFieldOption::destroy(
                $deletedChildren->pluck('id')->toArray()
            );

        }
    }

    /**
     * @param VersionField $record
     * @return void
     */
    public function updateChildren(VersionField $record): void
    {
        foreach ($this->input('options') as $child) {

            $record->options()->updateOrCreate(
                ['uuid' => $child['id'] ?? null],
                $child
            );

        }
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->ingestParameters();
        $this->ingestOptions();
    }
}
