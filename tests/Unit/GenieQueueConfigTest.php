<?php

namespace Tests\Unit;

use App\Enums\ImageRevisionMode;
use App\Genie\Runs\Runner;
use App\Jobs\AiRunJob;
use App\Jobs\RevisePostImageJob;
use App\Models\AiRun;
use App\Models\AiRunStepFile;
use App\Models\GeniePost;
use App\Models\RuleStep;
use Tests\TestCase;

class GenieQueueConfigTest extends TestCase
{
    public function test_genie_jobs_are_queued_on_the_connection_the_genie_supervisor_works(): void
    {
        $this->assertSame(AiRunJob::CONNECTION, config('horizon.defaults.genie-ai.connection'));
        $this->assertSame([AiRunJob::QUEUE], config('horizon.defaults.genie-ai.queue'));
        $this->assertSame('redis', config('queue.connections.'.AiRunJob::CONNECTION.'.driver'));

        $run = new AiRunJob(new AiRun);
        $revision = new RevisePostImageJob(new GeniePost, new AiRunStepFile, new RuleStep, ImageRevisionMode::FIX);

        $this->assertSame(AiRunJob::CONNECTION, $run->connection);
        $this->assertSame(AiRunJob::QUEUE, $run->queue);
        $this->assertSame(AiRunJob::CONNECTION, $revision->connection);
        $this->assertSame(AiRunJob::QUEUE, $revision->queue);
    }

    /**
     * A shorter retry_after makes Redis hand a job that is still drawing images to a second
     * worker; a claim that expires before the supervisor kills the job lets that worker in.
     */
    public function test_a_running_job_is_never_handed_to_a_second_worker(): void
    {
        $retryAfter = config('queue.connections.'.AiRunJob::CONNECTION.'.retry_after');
        $supervisorTimeout = config('horizon.defaults.genie-ai.timeout');
        $jobTimeout = (new AiRunJob(new AiRun))->timeout;

        $this->assertGreaterThan(Runner::CLAIM_EXPIRES_AFTER, $retryAfter);
        $this->assertGreaterThanOrEqual($supervisorTimeout, Runner::CLAIM_EXPIRES_AFTER);
        $this->assertGreaterThan($jobTimeout, $supervisorTimeout);
    }
}
