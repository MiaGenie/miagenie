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
        try {
            $upload = OpenAI::assistants()->modify(
                $assistant->assistant_provider_id,
                $this->assistantData($assistant)
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

    /**
     * @param Assistant $assistant
     * @return array
     */
    private function assistantData(Assistant $assistant): array
    {
        $assistantData = [
            'name' => $assistant->name,
            'description' => $assistant->description ?? '',
            'model' => $assistant->model,
            'instructions' => $assistant->instructions,
            'reasoning_effort' => $assistant->reasoning_effort
        ];

        if ($assistant->vector_id) {
            $vectorStore = Vector::find($assistant->vector_id)?->vector_provider_id;
            $assistantData['tools'] = [['type' => 'file_search']];
            $assistantData['tool_resources'] = ['file_search' => ['vector_store_ids' => [$vectorStore]]];
        }

        $responseFormat['type'] = $assistant->response_format;
        if ($assistant->response_format === 'json_schema') {
            $responseFormat['json_schema'] = json_decode($assistant->json_schema, true);
        }
        $assistantData['response_format'] = $responseFormat;

        if ($assistant->temperature !== '1') {
            $assistantData['temperature'] = (float)$assistant->temperature;
        }

        if ($assistant->top_p !== '1') {
            $assistantData['top_p'] = (float)$assistant->top_p;
        }

        if ($assistant->top_p !== '1') {
            $assistantData['top_p'] = (float)$assistant->top_p;
        }

        return $assistantData;
    }
}
