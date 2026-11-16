<?php

namespace App\Jobs;

use App\Enums\RunResponseError;
use App\Enums\RunStatus;
use App\Genie\Runs\Runners;
use App\Models\AiRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Throwable;

/**
 * Drives a run one step at a time, whatever its rule type.
 *
 * One job per step rather than one per run: a step is a single model call, which is the unit that
 * can time out, be retried, or stop for review. The job re-dispatches itself while the runner says
 * the run can continue. The runner is chosen by the run's rule type, so a strategy run and an
 * ideas run — and later a posts run — share this loop.
 */
class AiRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * The model call is the long pole; this, the SDK per-call timeout, the Horizon supervisor
     * timeout, the step claim and the queue's retry_after all cap each other, so they are kept in
     * step: the connection's retry_after is 720 seconds, Runner::CLAIM_EXPIRES_AFTER and the
     * `genie-ai` supervisor 660, this job 600, and the step's profile timeout stays below all.
     */
    public int $timeout = 600;

    /**
     * The `default` queue's supervisor kills a job after 60 seconds, which no model call — and no
     * image generation, which takes minutes — survives.
     */
    public const QUEUE = 'genie-ai';

    /**
     * The `redis` connection's 90-second retry_after would hand a job that is still drawing images
     * to a second worker; this connection's outlasts every job on the queue.
     */
    public const CONNECTION = 'genie-redis';

    /**
     * Wait progressively longer between retries, so a rate limit has time to clear.
     *
     * @var int[]
     */
    public array $backoff = [15, 60, 180];

    public function __construct(public AiRun $run)
    {
        // Queued bare: loaded relations are re-loaded on the worker through the global scopes,
        // before handle() has set the current workspace.
        $this->run = $run->withoutRelations();

        $this->onConnection(self::CONNECTION);
        $this->onQueue(self::QUEUE);
    }

    public function handle(): void
    {
        WorkspaceManager::setCurrent($this->run->workspace);

        $runner = Runners::for($this->run->rule->rule_type);

        // A released job comes back here; pick the failed step up again rather than stepping over it.
        if ($this->attempts() > 1) {
            $runner->retryFailed($this->run);
        }

        if ($runner->advance($this->run)) {
            self::dispatch($this->run->fresh());

            return;
        }

        $this->releaseIfRetryable();
    }

    /**
     * Give a failed step another go, with the same escalating backoff the old pipeline used.
     *
     * The runner catches its own exceptions so it can record where a step failed, which means the
     * job never throws and the queue's own retry would never trigger. Releasing explicitly is what
     * keeps a rate limit or a timeout from ending the whole run on the first try.
     */
    protected function releaseIfRetryable(): void
    {
        if (! $this->isRetryable()) {
            return;
        }

        $this->release($this->backoff[$this->attempts() - 1] ?? 180);
    }

    /**
     * Whether the run stopped on a failure that another attempt could get past.
     *
     * An exhausted credit balance, quota or spend limit does not clear within the backoff, so a
     * step the SDK failed with InsufficientCreditsException (recorded by Runner::fail() as
     * RunResponseError::INSUFFICIENT_CREDITS) ends the run rather than failing the same way on
     * every remaining attempt. A manual resume (Runner::retryFailed()) clears the error first.
     */
    protected function isRetryable(): bool
    {
        if ($this->run->fresh()->status !== RunStatus::ERROR) {
            return false;
        }

        if ($this->attempts() >= $this->tries) {
            return false;
        }

        return $this->run->steps()
            ->where('status', RunStatus::ERROR)
            ->where('error', RunResponseError::INSUFFICIENT_CREDITS)
            ->doesntExist();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Genie run failed', [
            'run_id' => $this->run->id,
            'rule_type' => $this->run->rule?->rule_type?->name,
            'exception' => $exception?->getMessage(),
        ]);

        $step = $this->run->currentStep();
        $type = $this->run->rule?->rule_type;

        $step && $type && Runners::supports($type)
            ? Runners::stateFor($type)->failed($step, $exception?->getMessage())
            : $this->run->update(['status' => RunStatus::ERROR]);
    }
}
