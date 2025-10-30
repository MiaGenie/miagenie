<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Contracts\GenieOutputContract;
use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use App\Enums\RunResponseError;
use App\Enums\RunResponseStatus;
use Illuminate\Support\Facades\Log;

class GenieOutputIdeas extends GenieOutput implements GenieOutputContract
{
    /**
     * @param GenieData $data
     */
    public function handle(GenieData $data): void
    {
        try {
            parent::handle($data);
            /** @var \App\Models\RunResponse  $model */
            $model = $data->getModel();
            $response = $data->getResponse();

            $model->update([
                'provider_status' => RunResponseStatus::fromName($response['status']),
                'output' => $response['output'],
                'output_text' => $response['output_text'],
                'error' => $response['error'] ? RunResponseError::fromName($response['error']['code']) : null,
                'error_details' => $response['error'] ? $response['error']['message'] : null,
                'incomplete_details' => $response['incomplete_details'] ? $response['incomplete_details']['reason'] : null,
            ]);

            $contentPillarData = $model->run->runIdea->strategy->content[$model->step->dependsOnField->code_name][$model->runFieldIterator->field_index]  ?? null;
            $contentPillar = $contentPillarData ? array_shift($contentPillarData) : null;

            $output = collect(json_decode($response['output'][0]['content'][0]['text'], true));

            $ideas = $output->flatMap(function (array $values, string $key) use ($contentPillar, $model) {
                $values = array_map(function ($value) use ($key, $contentPillar, $model) {
                    $value['funnel_stage'] = FunnelStage::fromName($key);
                    $value['content_pillar'] = $contentPillar;
                    $value['status'] = IdeaStatus::PENDING_REVIEW;
                    $value['source'] = IdeaSource::GENIE;
                    $value['run_response_id'] = $model->id;
                    return $value;
                }, $values, array_keys($values));
                return $values;
            });

            $strategy = $model->run->runIdea->strategy;

            $strategy->ideas()->createMany($ideas);


        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

}
