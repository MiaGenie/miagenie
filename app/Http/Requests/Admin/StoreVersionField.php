<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\HandlesSubFields;
use App\Concerns\Requests\IngestVersionFields;
use App\Constants\FormTypeDefaults;
use App\Enums\FormFieldFileType;
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

class StoreVersionField extends FormRequest
{
    use HandlesSubFields;
    use IngestVersionFields;

    private Version $version;

    public function rules(): array
    {
        $this->version ??= Version::firstOrFailByUuid($this->route('version'));

        return array_merge($this->subFieldRules(), [
            'group_type' => ['required', Rule::enum(VersionGroupType::class)],
            'field_type' => ['required', Rule::enum(FormFieldType::class)],
            'input_type' => ['nullable', Rule::enum(FormInputType::class)],
            'file_type' => ['nullable', Rule::enum(FormFieldFileType::class)],
            'name' => ['required', 'string', 'max:500'],
            'code_name' => [
                'required',
                new SnakeCase,
                Rule::unique(VersionField::class, 'code_name')
                    ->where('group_type', $this->input('group_type'))
                    ->where('version_id', $this->version->id), 'max:255',
            ],
            'options' => [new FieldOptions],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.code_name' => ['required', 'distinct', 'max:255'],
            'rows' => ['nullable', 'integer', 'between:'.FormTypeDefaults::ROWS_MIN.','.FormTypeDefaults::ROWS_MAX],
            'min_length' => ['nullable', 'integer', $this->filled('max_height') ? 'lt:max_length' : '', 'max:'.$this->getMaxLength()],
            'max_length' => ['nullable', 'integer', $this->filled('min_length') ? 'gt:min_length' : '', 'max:'.$this->getMaxLength()],
            'min_value' => ['nullable', 'numeric', 'lt:max_value'],
            'max_value' => ['nullable', 'numeric', 'gt:min_value'],
            'step' => ['nullable', 'numeric'],
            'is_identifier' => [
                new IsIdentifier,
                Rule::unique(VersionField::class, 'is_identifier')
                    ->where('is_identifier', true)
                    ->where('group_type', $this->input('group_type'))
                    ->where('version_id', $this->version->id),
            ],
            'required' => ['nullable', 'boolean'],
            'genie_required' => ['nullable', 'boolean'],
            'hidden' => ['nullable', 'boolean'],
            'is_linkable' => ['nullable', 'boolean'],
            'display_title' => ['nullable', 'boolean'],
            'display_grouped' => ['nullable', 'boolean'],
            'display_field_title' => ['nullable', 'boolean'],
            'display_item_title' => ['nullable', 'boolean'],
            'display_faq_title' => ['nullable', 'string', 'max:5000'],
            'display_faq_text' => ['nullable', 'string', 'max:5000'],
            'class' => ['nullable', 'string', 'max:255'],
            'block' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function handle(): VersionField
    {
        $this->version ??= Version::firstOrFailByUuid($this->route('version'));

        $position = VersionField::where('group_type', $this->input('group_type'))->max('position') + 1;

        return DB::transaction(function () use ($position) {
            $record = VersionField::create([
                'version_id' => $this->version->id,
                'group_type' => $this->input('group_type'),
                'name' => $this->input('name'),
                'code_name' => $this->input('code_name'),
                'description' => $this->input('description'),
                'sub_description' => $this->input('sub_description'),
                'field_type' => $this->input('field_type'),
                'input_type' => $this->input('input_type'),
                'file_type' => $this->input('file_type'),
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
                'hidden' => $this->input('hidden'),
                'is_linkable' => $this->input('is_linkable'),
                'display_title' => $this->input('display_title'),
                'display_grouped' => $this->input('display_grouped'),
                'display_field_title' => $this->input('display_field_title'),
                'display_item_title' => $this->input('display_item_title'),
                'display_faq_title' => $this->input('display_faq_title'),
                'display_faq_text' => $this->input('display_faq_text'),
                'class' => $this->input('class'),
                'block' => $this->input('block'),
                'position' => $position,
            ]);

            $this->handleChildren($record);

            return $record;
        });
    }

    public function handleChildren(VersionField $record): void
    {
        foreach ($this->input('options') as $child) {
            $record->options()->save(new VersionFieldOption($child));
        }

        $this->handleSubFields($record);
    }

    protected function prepareForValidation(): void
    {
        $this->ingestParameters();
        $this->ingestOptions();
    }
}
