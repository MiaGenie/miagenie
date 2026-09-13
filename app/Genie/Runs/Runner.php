<?php

namespace App\Genie\Runs;

use App\Ai\Agents\StepAgent;
use App\Ai\StepAgentFactory;
use App\Ai\StepResponse;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Enums\Modality;
use App\Enums\RunStatus;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\Log as RunLog;
use App\Models\RuleStep;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

/**
 * Runs one step of a run, and says whether the run can continue.
 *
 * The pipeline is deliberately linear: take the next step the run has not finished, prompt it
 * inside the run's conversation so it sees what came before, store what it produced, and stop if
 * the step is gated for review. What differs between a strategy run, an ideas run and later a
 * posts run — the gate, the prompt, the schema, where the output goes, what else changes status —
 * is what a subclass supplies; the loop itself is shared.
 */
abstract class Runner
{
    public function __construct(
        protected StepAgentFactory $agents,
        protected RunState $state,
        protected StepOutput $output,
        protected StepGate $gate,
    ) {}

    /**
     * The message sent for this step.
     */
    abstract protected function prompt(AiRunStep $runStep, string $locale): RunPrompt;

    /**
     * A code-defined schema for this step, or null to let the step's own configuration decide:
     * a json_schema step then compiles its schema from the version's sub-fields, any other is
     * prompted for free text.
     */
    protected function schema(AiRunStep $runStep, string $locale): ?SchemaDefinition
    {
        return null;
    }

    /**
     * Whether this step's response is stored through the output, or only kept as the turn's text.
     *
     * A context step primes the conversation and produces nothing to keep, so its prose is
     * recorded on the turn rather than treated as a missing result.
     */
    protected function producesOutput(AiRunStep $runStep): bool
    {
        return ! ($runStep->step?->rule_sub_type?->isContext() ?? false);
    }

    /**
     * How this run type is named in the log.
     */
    abstract protected function label(): string;

    /**
     * Advance the run by one step. Returns false when it cannot continue — finished, gated or
     * failed — so the caller knows not to dispatch again.
     *
     * A failed run stops here rather than carrying on: continuing would leave the failed step
     * behind for good, because nextStep() would count it as visited and pick the one after it.
     * Getting past a failure is a deliberate act — see retryFailed().
     */
    public function advance(AiRun $run): bool
    {
        if (in_array($run->status, [RunStatus::PENDING_REVIEW, RunStatus::ERROR], true)) {
            return false;
        }

        $step = $this->nextStep($run);

        if (! $step) {
            $this->state->finished($run);

            return false;
        }

        $runStep = $this->open($run, $step);

        $this->state->starting($runStep);

        return $this->run($runStep);
    }

    /**
     * The first step of the rule the run has not settled yet, and which still applies to it.
     *
     * Only a step that is finished with counts as settled — COMPLETE, or SKIPPED for one the rule
     * passed over. A step left in ERROR, OPEN or PENDING_REVIEW is deliberately not counted: a
     * gated step is held by advance() until a reviewer releases it into COMPLETE, and a failed one
     * has to be picked back up rather than stepped over.
     *
     * A step the gate rejects is recorded as skipped on the way past, so the run never asks the
     * same question twice and the page can still account for every step of the rule.
     */
    public function nextStep(AiRun $run): ?RuleStep
    {
        $settled = $run->steps()
            ->reorder()
            ->whereIn('status', [RunStatus::COMPLETE, RunStatus::SKIPPED])
            ->pluck('step_id')
            ->all();

        $remaining = $run->rule
            ->steps()
            ->whereNotIn('id', $settled ?: [0])
            ->oldest('position')
            ->get();

        foreach ($remaining as $step) {
            if ($this->gate->passes($run, $step)) {
                return $step;
            }

            $this->state->skipped($run, $step);
        }

        return null;
    }

    /**
     * Put a failed run back into a state where advance() will retry the step that failed.
     *
     * Returns false when there is nothing to retry, so a caller cannot loop on a healthy run.
     */
    public function retryFailed(AiRun $run): bool
    {
        $failed = $run->steps()
            ->reorder()
            ->where('status', RunStatus::ERROR)
            ->oldest('position')
            ->first();

        if (! $failed) {
            return false;
        }

        $failed->update([
            'status' => RunStatus::OPEN,
            'error' => null,
            'error_details' => null,
        ]);

        $run->update(['status' => RunStatus::RUNNING]);

        return true;
    }

    /**
     * Reserve the row before prompting, so a crashed call still leaves a trace of the attempt.
     *
     * A retry reuses the row left behind;
     */
    protected function open(AiRun $run, RuleStep $step): AiRunStep
    {
        $existing = $run->steps()->reorder()->where('step_id', $step->id)->first();

        if ($existing) {
            $existing->update(['status' => RunStatus::OPEN, 'error' => null, 'error_details' => null]);

            return $existing;
        }

        return $run->steps()->create([
            'step_id' => $step->id,
            'position' => $step->position,
            'modality' => Modality::TEXT,
            'status' => RunStatus::OPEN,
        ]);
    }

    protected function run(AiRunStep $runStep): bool
    {
        $run = $runStep->run;
        $locale = $run->workspace->locale ?? app()->getFallbackLocale();

        try {
            $agent = $this->agent($runStep, $locale);

            $this->joinConversation($agent, $run);

            $started = microtime(true);

            $prompt = $this->prompt($runStep, $locale);

            $response = $agent->prompt(
                $prompt->text(),
                provider: $this->agents->provider($runStep->step),
                timeout: $this->agents->timeout($runStep->step),
            );

            $this->pinConversation($run, $response->conversationId);

            $structured = $response instanceof StructuredAgentResponse
                ? $response->toArray()
                : $this->decode($response->text);

            $producesOutput = $this->producesOutput($runStep);

            if (! $producesOutput && $structured === null) {
                $structured = ['text' => (string) $response->text];
            }

            $runStep->update([
                'invocation_id' => $response->invocationId,
                'message_id' => $this->lastMessageId(),
                'output' => $structured,
                'duration' => (int) round(microtime(true) - $started),
            ]);

            if ($structured === null) {
                throw new \RuntimeException("Step [{$runStep->step_id}] returned no usable output.");
            }

            if ($producesOutput) {
                $this->output->write($runStep, $structured);
            }

            $this->log($prompt, $runStep, [
                'invocation_id' => $response->invocationId,
                'conversation_id' => $run->conversation_id,
                'message_id' => $runStep->message_id,
                'structured' => $structured,
            ]);

            $this->state->completed($runStep);

            return $runStep->fresh()->status === RunStatus::COMPLETE;
        } catch (Throwable $exception) {
            Log::error('Genie '.$this->label().' step failed', [
                'run_id' => $run->id,
                'run_step_id' => $runStep->id,
                'step_id' => $runStep->step_id,
                'exception' => $exception->getMessage(),
            ]);

            $runStep->update(['error' => StepResponse::error(null)]);

            $this->log($prompt ?? null, $runStep, ['error' => $exception->getMessage()]);

            $this->state->failed($runStep, $exception->getMessage());

            return false;
        }
    }

    /**
     * The agent for this step: plain text for a context step, one carrying a code-defined
     * schema when the run type supplies one, otherwise whatever the step's own configuration
     * calls for.
     */
    protected function agent(AiRunStep $runStep, string $locale): StepAgent
    {
        if (! $this->producesOutput($runStep)) {
            return $this->agents->makeText($runStep->step, $locale);
        }

        $definition = $this->schema($runStep, $locale);

        return $definition
            ? $this->agents->makeDefined($runStep->step, $locale, $definition)
            : $this->agents->make($runStep->step, $locale);
    }

    /**
     * Record the turn in genie_logs.
     *
     * @param  array<string, mixed>  $response
     */
    protected function log(?RunPrompt $prompt, AiRunStep $runStep, array $response): void
    {
        RunLog::create([
            'type' => GenieType::RUN_RESPONSE,
            'action' => GenieSyncAction::CREATE,
            'request' => $prompt?->describe() ?? ['step_id' => $runStep->step_id],
            'response' => $response,
            'duration' => $runStep->duration ?? 0,
        ]);
    }

    /**
     * The first step of a run opens the conversation; the rest continue it.
     */
    protected function joinConversation(StepAgent $agent, AiRun $run): void
    {
        $run->conversation_id
            ? $agent->continue($run->conversation_id, as: $run->workspace)
            : $agent->forParticipant($run->workspace);
    }

    protected function pinConversation(AiRun $run, ?string $conversationId): void
    {
        if ($run->conversation_id || blank($conversationId)) {
            return;
        }

        $run->update(['conversation_id' => $conversationId]);
    }

    /**
     * The transcript row this turn wrote, when the recording store is bound.
     */
    protected function lastMessageId(): ?string
    {
        $store = resolve(ConversationStore::class);

        return property_exists($store, 'lastAssistantMessageId')
            ? $store->lastAssistantMessageId
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decode(?string $text): ?array
    {
        $decoded = json_decode((string) $text, true);

        return is_array($decoded) ? $decoded : null;
    }
}
