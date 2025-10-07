<?php

namespace App\Http\Requests\Admin;

use App\Enums\GenieSyncStatus;
use Illuminate\Foundation\Http\FormRequest;
use App\Jobs\AssistantJob;
use App\Models\Assistant;

class DeleteAssistant extends FormRequest
{
    /**
     * @return void
     */
    public function handle(): void
    {
        $assistant = Assistant::firstOrFailByUuid($this->route('assistant'));

        $assistant->update([
            'status' => GenieSyncStatus::DELETING
        ]);

        AssistantJob::dispatch($assistant, 'delete');
    }
}
