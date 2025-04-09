<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Jobs\VectorJob;
use App\Models\Vector;

class DeleteVectors extends FormRequest
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
            $vector = Vector::find($id);

            if (!$vector) {
                continue;
            }

            $result = VectorJob::dispatch($vector, 'delete');
            $foo = $result;
        }
    }
}
