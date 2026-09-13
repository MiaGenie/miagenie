<?php

namespace App\Http\Requests\Admin;

use App\Genie\Versions\VersionExporter;
use App\Genie\Versions\VersionImporter;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File as FileRules;
use Illuminate\Validation\ValidationException;

class ImportVersion extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', FileRules::types(['json'])->max(5120)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => __('genie.version_import_invalid'),
        ];
    }

    /**
     * Create a new version from an exported file.
     *
     * @throws ValidationException
     * @throws \Throwable
     */
    public function handle(): Version
    {
        return app(VersionImporter::class)->import($this->payload());
    }

    /**
     * The decoded file, once its envelope has been checked to be a version export.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function payload(): array
    {
        $payload = json_decode((string) $this->file('file')->get(), true);

        if (! is_array($payload)) {
            $this->rejectFile();
        }

        $validator = Validator::make($payload, [
            'format' => ['required', 'in:'.VersionExporter::FORMAT],
            'format_version' => ['required', 'integer', 'in:'.VersionExporter::FORMAT_VERSION],
            'version' => ['required', 'array'],
            'version.name' => ['required', 'string', 'max:255'],
            'version.description' => ['nullable', 'string'],
            'fields' => ['present', 'array'],
            'fields.*' => ['array'],
            'fields.*.code_name' => ['required', 'string'],
            'fields.*.group_type' => ['required', 'integer'],
            'rules' => ['present', 'array'],
            'rules.*' => ['array'],
            'rules.*.steps' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            $this->rejectFile();
        }

        return $payload;
    }

    /**
     * @throws ValidationException
     */
    protected function rejectFile(): never
    {
        throw ValidationException::withMessages([
            'file' => __('genie.version_import_invalid'),
        ]);
    }
}
