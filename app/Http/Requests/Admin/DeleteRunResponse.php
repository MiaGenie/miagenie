<?php

namespace App\Http\Requests\Admin;

use App\Enums\GenieSyncAction;
use App\Enums\RunStatus;
use App\Models\RunResponse;
use Illuminate\Foundation\Http\FormRequest;
use App\Jobs\VectorJob;
use App\Models\Vector;
use Illuminate\Support\Facades\Log;

class DeleteRunResponse extends FormRequest
{
    /**
     * @return void
     */
    public function handle(): void
    {
        $response = RunResponse::firstOrFailByUuid($this->route('run_response'));

        $response->delete();

        $response->run->update(['status' => RunStatus::OPEN]);
    }
}
