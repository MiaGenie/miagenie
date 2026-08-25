<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Concerns\CleanAsterisks;
use App\Concerns\PersistsStepResponse;
use App\Contracts\GenieOutputContract;
use App\Enums\DraftStatus;
use App\Models\Draft;
use App\Models\RunResponse;
use Illuminate\Support\Facades\Log;

class GenieOutputDrafts extends GenieOutput implements GenieOutputContract
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

            if (empty($model->step->output)) {
                return;
            }

            $draftData = [
                'run_response_id' => $model->id,
                'idea_id' => $model->runIdeaResponse->runIdea->idea_id,
                'status' => DraftStatus::PENDING_REVIEW,
            ];
            $output = $this->structuredOutput($response) ?? [];
            foreach ($model->step->output as $stepOutput) {
                $draftData[$stepOutput] = $output[$stepOutput] ?? '';
            }

            Draft::create($draftData);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
