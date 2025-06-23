<?php

namespace App\Http\Requests\Admin;

use App\Enums\GenieSyncAction;
use Illuminate\Foundation\Http\FormRequest;
use App\Jobs\FileJob;
use App\Models\File;

class DeleteFiles extends FormRequest
{

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array']
        ];
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        foreach ($this->input('items') as $id) {
            $file = File::find($id);

            if (!$file) {
                continue;
            }

            $file->delete();

            $result = FileJob::dispatch($file, GenieSyncAction::DELETE);
        }
    }
}
