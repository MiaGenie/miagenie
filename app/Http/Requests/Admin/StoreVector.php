<?php

namespace App\Http\Requests\Admin;

use App\Enums\FileStatus;
use App\Enums\VectorType;
use Illuminate\Foundation\Http\FormRequest;
use App\Jobs\VectorJob;
use App\Models\Vector;
use Illuminate\Validation\Rule;

class StoreVector extends FormRequest
{

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'vector_type' => ['required', Rule::enum(VectorType::class)],
        ];
    }

    /**
     * @return Vector
     */
    public function handle(): Vector
    {
        $vector = Vector::create([
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'files' => $this->input('files'),
            'vector_type' => $this->input('vector_type'),
            'status' => FileStatus::CREATED,
        ]);

        VectorJob::dispatch($vector, 'upload');

        return $vector;
    }
}
