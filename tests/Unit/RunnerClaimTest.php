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
use Tests\TestCase;

/**
 * Runner::claim() is what keeps a second worker off a step another worker is running. The suite
 * has no migrated database, so the run steps table is built in memory for these tests.
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
            $table->string('invocation_id')->nullable();
            $table->uuid('message_id')->nullable();
            $table->json('output')->nullable();
            $table->tinyInteger('error')->nullable();
            $table->string('error_details')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->timestamps();
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

    protected function claim(): ?AiRunStep
    {
        $runner = new class extends PostsRunner
        {
            public function claimFor(AiRun $run, RuleStep $step): ?AiRunStep
            {
                return $this->claim($run, $step);
            }
        };

        return $runner->claimFor($this->run, $this->step);
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
