<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Actions\GenieOutput;
use App\Contracts\GenieOutputContract;
use App\Enums\RuleSubType;
use App\Enums\RunResponseStatus;
use App\Enums\ThreadRunErrors;
use App\Enums\ThreadRunStatus;
use App\Enums\RunStatus;
use App\Models\Thread;
use App\Models\ThreadRun;
use Illuminate\Support\Facades\Log;

class GenieOutputStrategy extends GenieOutput implements GenieOutputContract
{
    /**
     * @param GenieData $data
     * @return ?GenieData
     */
    public function handle(GenieData $data): ?GenieData
    {
        try {
            $data = parent::handle($data);
            $model = $data->getModel();
            $response = $data->getResponse();

            $model->update([
                'provider_status' => RunResponseStatus::fromName($response['status']),
                'output' => $response['output'],
                'output_text' => $response['output_text'],
                'error' => $response['error'] ? strtoupper($response['error']['code']) : null,
                'error_details' => $response['error'] ? $response['error']['message'] : null,
                'incomplete_details' => $response['incomplete_details'] ? $response['incomplete_details']['reason'] : null,
            ]);

            $strategy = $model->run->strategy;
            $content = $strategy->content ?? [];
            switch ($model->step->rule_sub_type) {
                default:
                case RuleSubType::BRIEFINGS:
                    switch ($model->step->assistant->response_format) {
                        default:
                        case 'text':
                            $content[$model->step->output] = $response['output_text'];
                            break;
                        case 'json_schema':
                            $content[$model->step->output] = json_decode($response['output_text']);
                            break;
                    }
                    break;
                case RuleSubType::COMPETITORS:
                    $content[$model->step->output][$model->runCompetitor->competitor_id] = $response['output_text'];
                    break;
            }
            $strategy->content = $content;
            $strategy->save();
            return $data;

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return null;
        }
    }

    /**
     * @param Thread $thread
     * @param array $response
     * @return void
     */
    public function statusOutput(Thread $thread, array $response): void
    {
        $errorCode = $response['last_error'] ? ThreadRunErrors::fromName(
            $response['last_error']['code']
        ) : null;

        ThreadRun::find($response['id'])->update([
            'status' => ThreadRunStatus::fromName($response['status']),
            'error' => $errorCode,
            'error_details' => $response['last_error']['message'] ?? null,
            'incomplete_details' => $response['incomplete_details'] ?? null,
        ]);

        switch (strtoupper($response['status'])) {
            case 'REQUIRES_ACTION':
            case 'CANCELLING':
            case 'CANCELLED':
            case 'FAILED':
            case 'INCOMPLETE':
            case 'EXPIRED':
                $thread->update([
                    'status' => 'ERROR',
                ]);
                break;
        }
    }

    /**
     * @param Thread $thread
     * @param array $response
     * @return void
     */
    public function createOutput(Thread $thread, array $response): void
    {
        $thread->update([
            'thread_provider_id' => $response['id'],
            'status' => RunStatus::fromName('RUNNING'),
        ]);
    }

    /**
     * @param Thread $thread
     * @param array $response
     * @param GenieData $data
     * @return void
     */
    public function UpdateOutput(Thread $thread, array $response, GenieData $data): void
    {
        $currentStep = $data->currentStep();

        ThreadRun::create([
            'thread_id' => $thread->id,
            'step_id' => $currentStep->id,
            'run_provider_id' => $response['id'],
            'status' => ThreadRunStatus::QUEUED,
        ]);
    }

    /**
     * @param array $response
     * @return void
     */
    public function messageOutput(array $response): void
    {
        ThreadRun::find($response['id'])->update([
            'status' => strtoupper($response['status']),
            'error' => $response['error'] ? strtoupper($response['error']['code']) : null,
            'error_details' => $response['error'] ? $response['error']['message'] : null,
            'incomplete_details' => $response['incomplete_details'] ? $response['incomplete_details']['reason'] : null,
        ]);
    }

}
