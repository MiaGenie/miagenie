<?php

namespace App\Jobs;

use App\Abstracts\GenieData;
use App\Concerns\GenieLogger;
use App\Contracts\GenieOutputContract;
use App\Contracts\ThreadAction;
use App\Models\Rule;
use App\Models\ThreadRun;
use App\Models\Thread;
use Illuminate\Support\Facades\App;
use Inovector\Mixpost\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ThreadJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use GenieLogger;

    /**
     * @var int
     */
    public int $tries = 3;

    /**
     * @var int
     */
    public int $timeout = 30;

    /**
     * @var Rule
     */
    private Rule $rule;

    /**
     * @var Thread
     */
    private Thread $thread;

    /**
     * @var ThreadAction
     */
    private ThreadAction $threadAction;

    /**
     * @var string
     */
    private string $action;

    /**
     * @param string $action
     * @param Thread $thread
     */
    public function __construct(Thread $thread, string $action)
    {
        $this->action = $action;
        $this->thread = $thread;
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(): void
    {
        $data = $this->getData();
        $aiAction = $this->getAiAction();

        $data = $aiAction->handle($data);

        $genieOutput = $this->getGenieOutput($data);

        $data = $genieOutput->handle($data);

        $this->logRun('thread', $this->action, $data);

        $nextAction = $data->nextAction();
        if ($nextAction) {
            ThreadJob::dispatch($this->thread, $nextAction);
        }

    }

    /**
     * @param Throwable|null $exception
     * @return void
     */
    public function failed(?Throwable $exception): void
    {

    }

    /**
     * @return GenieData|mixed|object
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getData(): mixed
    {
        return App::make(
            GenieData::class,
            [
                'action' => $this->action,
                'model' => $this->thread,
                'rule_type' => $this->thread->rule->rule_type->name,
            ]
        );
    }

    /**
     * @return ThreadAction|mixed|object
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getAiAction(): mixed
    {
        return App::make(
            ThreadAction::class,
            [
                'thread' => $this->thread,
                'action' => $this->action
            ]
        );
    }


    /**
     * @return GenieOutputContract|mixed|object
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getGenieOutput(GenieData $data): mixed
    {
        return App::make(
            GenieOutputContract::class,
            [
                'data' => $data,
                'type' => $data->getType()
            ]
        );
    }
}
