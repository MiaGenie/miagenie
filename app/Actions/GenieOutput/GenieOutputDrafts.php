<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Concerns\CleanAsterisks;
use App\Contracts\GenieOutputContract;
use App\Enums\DraftStatus;
use App\Enums\RunResponseError;
use App\Enums\RunResponseStatus;
use App\Models\Draft;
use Illuminate\Support\Facades\Log;

class GenieOutputDrafts extends GenieOutput implements GenieOutputContract
{
    use CleanAsterisks;

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

            if (empty($model->step->output)) {
                return;
            }

            $draftData = [
                'run_response_id' => $model->id,
                'idea_id' => $model->runIdeaResponse->runIdea->idea_id,
                'status' => DraftStatus::PENDING_REVIEW,
            ];
            $responseOutput = $response['output'][0]['content'][0]['text'];
            $responseOutput = $this->cleanAsterisks($responseOutput);
            $output = json_decode($responseOutput, true);
            foreach ($model->step->output as $stepOutput) {
                $draftData[$stepOutput] = $output[$stepOutput] ?? '';
            }

            Draft::create($draftData);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

}
