<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Jobs\AssistantJob;
use App\Models\Assistant;

class DeleteAssistants extends FormRequest
{
    /**
     * @return void
     */
    public function handle(): void
    {
        $assistant = Assistant::firstOrFailByUuid($this->route('assistant'));

        AssistantJob::dispatch($assistant, 'delete');
    }
}
