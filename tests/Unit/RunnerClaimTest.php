<?php

namespace Tests\Unit;

use App\Enums\Modality;
use App\Enums\RunResponseError;
use App\Enums\RunStatus;
use App\Genie\Posts\PostsRunner;
use App\Genie\Runs\Runner;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\RuleStep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Runner::claim() is what keeps a second worker off a step another worker is running, while letting
 * the job that holds it take it back on its own retry. The suite has no migrated database, so the
 * run steps table is built in memory for these tests, with the unique index of migration 0057.
 */
class RunnerClaimTest extends TestCase
{
    protected AiRun $run;

    protected RuleStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.runner_claim', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'runner_claim');

        Schema::create('genie_ai_run_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('run_id');
            $table->unsignedBigInteger('step_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->tinyInteger('modality')->nullable();
            $table->tinyInteger('status');
            $table->char('claimed_by', 36)->nullable();
            $table->string('invocation_id')->nullable();
            $table->uuid('message_id')->nullable();
            $table->json('output')->nullable();
            $table->tinyInteger('error')->nullable();
            $table->string('error_details')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->timestamps();

            $table->unique(['run_id', 'step_id']);
        });

        $this->freezeSecond();

        $this->run = (new AiRun)->forceFill(['id' => 7]);
        $this->step = (new RuleStep)->forceFill(['id' => 21, 'position' => 3, 'modality' => Modality::IMAGE]);
    }

    public function test_an_open_step_is_claimed_for_this_worker(): void
    {
        $row = $this->row(RunStatus::OPEN);

        $claimed = $this->claim();

        $this->assertNotNull($claimed);
        $this->assertSame($row->id, $claimed->id);
        $this->assertSame(RunStatus::RUNNING, $claimed->status);
        $this->assertSame(Modality::IMAGE, $claimed->modality);
    }

    public function test_of_two_workers_claiming_the_same_step_only_the_first_gets_it(): void
    {
        $this->row(RunStatus::OPEN);

        $this->assertNotNull($this->claim());
        $this->assertNull($this->claim());
        $this->assertSame(1, AiRunStep::count());
    }

    public function test_a_step_another_worker_is_running_is_refused_and_left_alone(): void
    {
        $row = $this->row(RunStatus::RUNNING, now()->subSeconds(Runner::CLAIM_EXPIRES_AFTER - 1));

        $this->assertNull($this->claim());

        $fresh = $row->fresh();
        $this->assertSame(RunStatus::RUNNING, $fresh->status);
        $this->assertTrue($fresh->updated_at->equalTo($row->updated_at));
    }

    public function test_a_step_left_running_by_a_worker_that_is_gone_is_taken_over(): void
    {
        $row = $this->row(RunStatus::RUNNING, now()->subSeconds(Runner::CLAIM_EXPIRES_AFTER));

        $claimed = $this->claim();

        $this->assertNotNull($claimed);
        $this->assertSame($row->id, $claimed->id);
        $this->assertSame(RunStatus::RUNNING, $claimed->status);
        $this->assertTrue($claimed->updated_at->equalTo(now()));
    }

    public function test_a_failed_step_is_claimed_with_its_error_cleared(): void
    {
        $this->row(RunStatus::ERROR, null, [
            'error' => RunResponseError::SERVER_ERROR,
            'error_details' => 'The image model timed out.',
        ]);

        $claimed = $this->claim();

        $this->assertNotNull($claimed);
        $this->assertSame(RunStatus::RUNNING, $claimed->status);
        $this->assertNull($claimed->error);
        $this->assertNull($claimed->error_details);
    }

    public function test_a_skipped_step_reopened_for_a_regeneration_is_claimed(): void
    {
        $this->row(RunStatus::SKIPPED);

        $this->assertSame(RunStatus::RUNNING, $this->claim()?->status);
    }

    public function test_a_step_without_a_row_gets_one_running(): void
    {
        $claimed = $this->claim();

        $this->assertNotNull($claimed);
        $this->assertSame(RunStatus::RUNNING, $claimed->status);
        $this->assertSame(7, (int) $claimed->run_id);
        $this->assertSame(21, (int) $claimed->step_id);
        $this->assertSame(3, (int) $claimed->position);
        $this->assertSame(Modality::IMAGE, $claimed->modality);
        $this->assertSame(1, AiRunStep::count());
    }

    public function test_a_claim_records_the_job_that_holds_the_step(): void
    {
        $this->row(RunStatus::OPEN);

        $this->assertSame('job-a', $this->claim('job-a')?->claimed_by);
    }

    public function test_a_first_claim_records_the_job_that_holds_the_step(): void
    {
        $this->assertSame('job-a', $this->claim('job-a')?->claimed_by);
    }

    /**
     * A job that threw after claiming is released for a retry with the same uuid. Nothing else
     * would pick its RUNNING step up before the claim expires, so it takes the step back at once.
     */
    public function test_the_job_that_claimed_a_running_step_takes_it_back_on_its_retry(): void
    {
        $row = $this->row(RunStatus::RUNNING, now()->subSeconds(15), ['claimed_by' => 'job-a']);

        $claimed = $this->claim('job-a');

        $this->assertNotNull($claimed);
        $this->assertSame($row->id, $claimed->id);
        $this->assertSame(RunStatus::RUNNING, $claimed->status);
        $this->assertTrue($claimed->updated_at->equalTo(now()));
    }

    public function test_another_job_is_refused_a_step_claimed_by_a_job_still_running_it(): void
    {
        $row = $this->row(RunStatus::RUNNING, now()->subSeconds(15), ['claimed_by' => 'job-a']);

        $this->assertNull($this->claim('job-b'));
        $this->assertNull($this->claim());

        $fresh = $row->fresh();
        $this->assertSame('job-a', $fresh->claimed_by);
        $this->assertTrue($fresh->updated_at->equalTo($row->updated_at));
    }

    public function test_a_step_left_running_by_a_job_that_is_gone_passes_to_the_next(): void
    {
        $this->row(RunStatus::RUNNING, now()->subSeconds(Runner::CLAIM_EXPIRES_AFTER), ['claimed_by' => 'job-a']);

        $this->assertSame('job-b', $this->claim('job-b')?->claimed_by);
    }

    /**
     * Two jobs that both found no row race to create it; the unique index lets one insert land
     * and the other job stops, rather than both running the step.
     */
    public function test_of_two_jobs_creating_the_first_row_the_one_that_loses_is_refused(): void
    {
        AiRunStep::creating(function (AiRunStep $runStep): void {
            if ($runStep->claimed_by !== 'job-b') {
                return;
            }

            AiRunStep::query()->toBase()->insert([
                'uuid' => (string) Str::uuid(),
                'run_id' => $this->run->id,
                'step_id' => $this->step->id,
                'position' => $this->step->position,
                'status' => RunStatus::RUNNING->value,
                'claimed_by' => 'job-a',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->assertNull($this->claim('job-b'));

        $row = AiRunStep::sole();
        $this->assertSame('job-a', $row->claimed_by);
        $this->assertSame(RunStatus::RUNNING, $row->status);
    }

    protected function claim(?string $claimant = null): ?AiRunStep
    {
        $runner = new class extends PostsRunner
        {
            public function claimFor(AiRun $run, RuleStep $step, ?string $claimant): ?AiRunStep
            {
                return $this->claim($run, $step, $claimant);
            }
        };

        return $runner->claimFor($this->run, $this->step, $claimant);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function row(RunStatus $status, ?Carbon $updatedAt = null, array $attributes = []): AiRunStep
    {
        $row = AiRunStep::create([
            'run_id' => $this->run->id,
            'step_id' => $this->step->id,
            'position' => $this->step->position,
            'modality' => Modality::TEXT,
            'status' => $status,
            ...$attributes,
        ]);

        if ($updatedAt) {
            AiRunStep::query()->whereKey($row->id)->toBase()->update(['updated_at' => $updatedAt]);
        }

        return $row->fresh();
    }
}
