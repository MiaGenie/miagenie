<?php

namespace App\Http\Requests\Admin;

use App\Enums\GenieSyncAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File as FileRules;
use App\Jobs\FileJob;
use App\Models\File;
use App\Support\FileUploader;

class UploadFile extends FormRequest
{

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'file' => ['required', FileRules::types($this->allowedTypes())->max($this->max())]
        ];
    }

    /**
     * @return File
     */
    public function handle(): File
    {

        $file = FileUploader::fromFile($this->file('file'))
            ->path('genie')
            ->uploadAndInsert();

        FileJob::dispatch($file, GenieSyncAction::CREATE);

        return $file;

    }

    /**
     * @return array
     */
    public function messages(): array
    {
        if (!$this->file('file')) {
            return [
                'file.required' => __('mixpost::genie.file_required')
            ];
        }

        $max = $this->max() / 1024;

        return [
            'file.max' => __('mixpost::genie.file_max_size', ['type' => 'File', 'max' => $max]),
        ];
    }

    /**
     * @return int
     */
    private function max()
    {
        return File::maxFileSize();
    }

    /**
     * @return array
     */
    private function allowedTypes(): array
    {
        return array_keys(File::mimeTypes());
    }
}
