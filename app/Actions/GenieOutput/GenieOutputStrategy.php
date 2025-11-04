<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Concerns\CleanAsterisks;
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

            $strategy = $model->run->strategy;
            $content = $strategy->content ?? [];
            $firstOutput = $model->step->output[0];
            $outpufField = $strategy->run->rule->version->fields->where('code_name', $firstOutput)->first();
            $outputText = $response['output'][0]['content'][0]['text'];
            $outputText = $this->cleanAsterisks($outputText);
            switch ($model->step->rule_sub_type) {
                default:
                case RuleSubType::BRIEFINGS:
                case RuleSubType::CHANNELS:
                    switch ($model->step->response_format) {
                        default:
                        case 'text':
                            $content[$firstOutput] = $outputText;
                            break;
                        case 'json_schema':
                            $responseOutput = json_decode($outputText, true);
                            if ($outpufField->field_type->name === 'CHECKBOX') {
                                $content[$firstOutput] = array_keys($responseOutput[$firstOutput], true);
                            } else {
                                $content[$firstOutput] = $responseOutput[$firstOutput];
                            }
                            break;
                    }
                    break;
                case RuleSubType::BRIEFINGS_MULTIPLE:
                    $responseOutput = json_decode($outputText, true);
                    foreach ($model->step->output as $output) {
                        $content[$output] = $responseOutput[$output];
                    }
                    break;
                case RuleSubType::COMPETITORS:
                    switch ($model->step->response_format) {
                        default:
                        case 'text':
                            $content[$firstOutput][$model->runCompetitor->competitor_id] = $outputText;
                            break;
                        case 'json_schema':
                            $responseOutput = json_decode($outputText, true);
                            $content[$firstOutput][$model->runCompetitor->competitor_id] = $responseOutput;
                            break;
                    }
                    break;
            }
            $strategy->content = $content;
            $strategy->update();

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

}
