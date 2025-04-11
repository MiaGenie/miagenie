<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Jobs\VectorJob;
use App\Models\Vector;

class DeleteVector extends FormRequest
{
    /**
     * @return void
     */
    public function handle(): void
    {
        $vector = Vector::firstOrFailByUuid($this->route('vector'));

        VectorJob::dispatch($vector, 'delete');
    }
}
