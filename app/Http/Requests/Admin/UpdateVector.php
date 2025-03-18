<?php

namespace App\Http\Requests\Admin;

use App\Enums\VectorType;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Vector;
use Illuminate\Validation\Rule;

class UpdateVector extends FormRequest
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
     * @return int
     */
    public function handle(): int
    {
        $record = Vector::firstOrFailByUuid($this->route('vector'));

        return $record->update([
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'files' => $this->input('files'),
            'vector_type' => $this->input('vector_type')
        ]);
    }

}
