<?php

namespace App\Actions\GenieRun;

use App\Abstracts\GenieData;
use App\Ai\Agents\StepAgent;
use App\Ai\StepAgentFactory;
use App\Ai\StepResponse;
use App\Contracts\GenieSyncContract;
use App\Enums\RuleType;
use App\Models\Run;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

class CreateResponse implements GenieSyncContract
{
    public function __construct(protected StepAgentFactory $agents = new StepAgentFactory) {}

    public function handle(GenieData $data): ?GenieData
    {
        $model = $data->getModel();
        $step = $model->step;
        $run = $model->run;

        try {
            $agent = $this->agents->make($step, $data->getLocale());

            $this->joinConversation($agent, $run);

            $startTime = time();

            $response = $agent->prompt(
                $data->getPrompt(),
                provider: $this->agents->provider($step),
                timeout: $this->agents->timeout($step),
            );

            $data->setDuration(time() - $startTime);
            $data->setResponse(StepResponse::fromAgentResponse($response));
            $data->setResponseStatus();

            $this->rememberConversation($run, $response);

            return $data;
        } catch (Throwable $exception) {
            Log::error('Genie step failed', [
                'step_id' => $step->id,
                'run_response_id' => $data->getModel()->id,
                'exception' => $exception->getMessage(),
            ]);

            $data->setError(true);
            $data->setResponse(StepResponse::fromThrowable($exception));

            return $data;
        }
    }

    /**
     * Put the step into its run's conversation, so the model sees the steps that came before it.
     *
     * The first step of a run starts the conversation; the rest continue it. The workspace is the
     * participant, which is what makes a run's conversation listable per tenant.
     *
     * Strategy only for now: Ideas, Drafts and PrePosts are being reworked separately and keep
     * their existing single-turn behaviour until then.
     */
    protected function joinConversation(StepAgent $agent, Run $run): void
    {
        if ($run->rule->rule_type !== RuleType::STRATEGY) {
            return;
        }

        $run->conversation_id
            ? $agent->continue($run->conversation_id, as: $run->workspace)
            : $agent->forParticipant($run->workspace);
    }

    /**
     * Pin the conversation the middleware created onto the run, so the next step continues it.
     */
    protected function rememberConversation(Run $run, AgentResponse $response): void
    {
        if ($run->rule->rule_type !== RuleType::STRATEGY
            || $run->conversation_id
            || blank($response->conversationId)) {
            return;
        }

        $run->update(['conversation_id' => $response->conversationId]);
    }
}
