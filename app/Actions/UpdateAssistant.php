<?php

namespace App\Actions;

use App\Enums\OpenAISyncStatus;
use App\Models\Assistant;
use App\Models\Vector;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class UpdateAssistant
{
    public function __invoke(Assistant $assistant): bool
    {
        if ($assistant->vector_id) {
            $vectorDB = Vector::find($assistant->vector_id);
            $assistantToolResource = ['file_search' => ['vector_store_ids' => [$vectorDB->vector_provider_id]]];

            $assistantTools = [
                ['type' => 'file_search'],
            ];
        }

        $assistantResponseFormat = ['type' => $assistant->response_format];

        if ($assistant->response_format === 'json_schema') {
            $assistantResponseFormat['json_schema'] = $assistant->json_schema;
        }

        try {
            $upload = OpenAI::assistants()->modify(
                $assistant->assistant_provider_id,
                [
                    'model' => $assistant->model,
                    'description' => $assistant->description ?? '',
                    'instructions' => $assistant->instructions,
                    'name' => $assistant->name,
                    'tools' => $assistantTools ?? [],
                    'tool_resources' => $assistantToolResource ?? null,
                    'response_format' => $assistantResponseFormat,
                    'temperature' => (float)$assistant->temperature,
                    'top_p' => (float)$assistant->top_p,
                ]
            );

            if ($upload->id && $upload->object === 'assistant') {
                $assistantDb = Assistant::find($assistant->id);
                $assistantDb->status = OpenAISyncStatus::UPDATED;
                $assistantDb->save();

                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
