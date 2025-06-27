<?php

namespace App\Http\Requests\Admin;

use App\Enums\GenieSyncAction;
use Illuminate\Foundation\Http\FormRequest;
use App\Jobs\VectorJob;
use App\Models\Vector;
use Illuminate\Support\Facades\Log;

class DeleteVector extends FormRequest
{
    /**
     * @return void
     */
    public function handle(): void
    {
        $vector = Vector::firstOrFailByUuid($this->route('vector'));

        $vector->delete();

        VectorJob::dispatch($vector, GenieSyncAction::DELETE);
    }
}
