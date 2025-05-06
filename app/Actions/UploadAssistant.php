<?php

namespace App\Actions;

use App\Enums\OpenAISyncStatus;
use App\Models\Assistant;
use App\Models\Vector;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class UploadAssistant
{
    public function __invoke(Assistant $assistant): bool
    {
        try {
            $upload = OpenAI::assistants()->create($this->assistantData($assistant));

            Log::info('OPENAI - upload - ' . json_encode($upload, true));

            if ($upload->id && $upload->object === 'assistant') {
                $assistantDb = Assistant::find($assistant->id);
                $assistantDb->assistant_provider_id = $upload->id;
                $assistantDb->status = OpenAISyncStatus::UPLOADED;
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
            'description' => $assistant->description,
            'model' => $assistant->model,
            'instructions' => $assistant->instructions
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

        if ($assistant->temperature) {
            $assistantData['temperature'] = (float)$assistant->temperature;
        }

        if ($assistant->top_p) {
            $assistantData['top_p'] = (float)$assistant->top_p;
        }

        if ($assistant->reasoning_effort) {
            $assistantData['reasoning_effort'] = 'medium';
        }

        return $assistantData;
    }
}
