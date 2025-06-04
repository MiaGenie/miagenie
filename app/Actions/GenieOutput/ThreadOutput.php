<?php

namespace App\Actions\GenieOutput;

use App\Abstracts\GenieData;
use App\Contracts\GenieOutputContract;
use App\Enums\ThreadRunErrors;
use App\Enums\ThreadRunStatus;
use App\Enums\ThreadStatus;
use App\Models\Thread;
use App\Models\ThreadRun;
use Illuminate\Support\Facades\Log;

class ThreadOutput implements GenieOutputContract
{
    /**
     * @param GenieData $data
     * @return ?GenieData
     */
    public function handle(GenieData $data): ?GenieData
    {
        $action = $data->getAction();
        $thread = $data->getModel();
        $response = $data->getResponse();
        try {
            switch ($action) {
                case 'create':
                    $this->createOutput($thread, $response);
                    break;
                case 'update':
                    $this->UpdateOutput($thread, $response, $data);
                    break;
                case 'status':
                    $this->statusOutput($thread, $response);
                    break;
                case 'message':
                    $this->messageOutput($response);
                    break;
            }

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
        $errorCode = $response['last_error']['code'] ? ThreadRunErrors::fromName(
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
            'status' => ThreadStatus::fromName('RUNNING'),
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
            'error' => strtoupper($response['last_error']['code']),
            'error_details' => $response['last_error']['message'],
            'incomplete_details' => $response['incomplete_details'],
        ]);
    }

}
