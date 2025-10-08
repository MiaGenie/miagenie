<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Contracts\GenieOutputContract;
use App\Enums\RuleSubType;
use App\Enums\RunResponseError;
use App\Enums\RunResponseStatus;
use App\Enums\RunStatus;
use App\Models\Thread;
use App\Models\ThreadRun;
use Illuminate\Support\Facades\Log;

class GenieOutputStrategy extends GenieOutput implements GenieOutputContract
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

            $strategy = $model->run->strategy;
            $content = $strategy->content ?? [];
            $firstOutput = $model->step->output[0];
            switch ($model->step->rule_sub_type) {
                default:
                case RuleSubType::BRIEFINGS:
                    switch ($model->step->response_format) {
                        default:
                        case 'text':
                            $content[$firstOutput] = $response['output'][0]['content'][0]['text'];
                            break;
                        case 'json_schema':
                            $responseOutput = json_decode($response['output'][0]['content'][0]['text'], true);
                            $content[$firstOutput] = $responseOutput[$firstOutput];
                            break;
                    }
                    break;
                case RuleSubType::BRIEFINGS_MULTIPLE:
                    $responseOutput = json_decode($response['output'][0]['content'][0]['text'], true);
                    foreach ($model->step->output as $output) {
                        $content[$output] = $responseOutput[$output];
                    }
                    break;
                case RuleSubType::COMPETITORS:
                    $content[$firstOutput][$model->runCompetitor->competitor_id] = $response['output'][0]['content'][0]['text'];
                    break;
            }
            $strategy->content = $content;
            $strategy->update();

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

}
