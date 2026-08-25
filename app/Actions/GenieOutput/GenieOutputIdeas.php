<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Concerns\CleanAsterisks;
use App\Concerns\PersistsStepResponse;
use App\Contracts\GenieOutputContract;
use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use App\Models\RunResponse;
use Illuminate\Support\Facades\Log;

class GenieOutputIdeas extends GenieOutput implements GenieOutputContract
{
    use CleanAsterisks;
    use PersistsStepResponse;

    public function handle(GenieData $data): void
    {
        try {
            parent::handle($data);
            /** @var RunResponse $model */
            $model = $data->getModel();
            $response = $data->getResponse();

            $this->persistResponse($model, $response);

            $contentPillarData = $model->run->runStrategy->strategy->content[$model->step->dependsOnField->code_name][$model->runFieldIterator->field_index] ?? null;
            $contentPillar = $contentPillarData ? array_shift($contentPillarData) : null;

            $output = collect($this->structuredOutput($response) ?? []);

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

            $strategy = $model->run->runStrategy->strategy;

            $strategy->ideas()->createMany($ideas);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
