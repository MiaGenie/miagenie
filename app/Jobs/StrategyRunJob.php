<?php

namespace App\Jobs;

use App\Enums\RunStatus;
use App\Genie\Strategy\StrategyRunner;
use App\Genie\Strategy\StrategyRunState;
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
 * Drives a strategy run one step at a time.
 *
 * One job per step rather than one per run: a step is a single model call, which is the unit that
 * can time out, be retried, or stop for review. The job re-dispatches itself while the runner says
 * the run can continue.
 */
class StrategyRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * The model call is the long pole; this, the SDK per-call timeout and the Horizon supervisor
     * timeout all cap each other, so they are kept in step.
     */
    public int $timeout = 600;

    public const QUEUE = 'default';

    /**
     * Wait progressively longer between retries, so a rate limit has time to clear.
     *
     * @var int[]
     */
    public array $backoff = [15, 60, 180];

    public function __construct(public AiRun $run)
    {
        $this->onQueue(self::QUEUE);
    }

    public function handle(StrategyRunner $runner): void
    {
        WorkspaceManager::setCurrent($this->run->workspace);

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
        if ($this->run->fresh()->status !== RunStatus::ERROR) {
            return;
        }

        if ($this->attempts() >= $this->tries) {
            return;
        }

        $this->release($this->backoff[$this->attempts() - 1] ?? 180);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Genie strategy run failed', [
            'run_id' => $this->run->id,
            'exception' => $exception?->getMessage(),
        ]);

        $step = $this->run->currentStep();

        $step
            ? app(StrategyRunState::class)->failed($step, $exception?->getMessage())
            : $this->run->update(['status' => RunStatus::ERROR]);
    }
}
