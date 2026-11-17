<?php

namespace Tests\Unit;

use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Models\AiRun;
use App\Models\AiRunStep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A run step's tokens and a run's tokens are the sums of their log entries, never stored. The
 * suite has no migrated database, so the tables are built in memory; no workspace is current, as
 * in the admin.
 */
class RunTokenCountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.run_token_counts', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'run_token_counts');

        Schema::create('genie_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('genie_ai_run_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('run_id');
            $table->unsignedInteger('position')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('genie_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type');
            $table->string('action');
            $table->unsignedBigInteger('run_step_id')->nullable();
            $table->smallInteger('duration')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamps();
        });

        // Run 1: a text step called twice (a retry), and an image step drawn before calls were counted.
        $this->insertRun(1, workspaceId: 7);
        $this->insertStep(10, runId: 1, position: 1);
        $this->insertStep(11, runId: 1, position: 2);
        $this->insertEntry(runStepId: 10, input: 2280, output: 829);
        $this->insertEntry(runStepId: 10, input: null, output: null);
        $this->insertEntry(runStepId: 10, input: 2300, output: 900);
        $this->insertEntry(runStepId: 11, input: null, output: null);

        // Run 2, of another workspace, and an entry that belongs to no run.
        $this->insertRun(2, workspaceId: 8);
        $this->insertStep(20, runId: 2, position: 1);
        $this->insertEntry(runStepId: 20, input: 40522, output: 1829);
        $this->insertEntry(runStepId: null, input: 99, output: 99);
    }

    public function test_a_step_adds_up_every_call_logged_on_it(): void
    {
        $steps = AiRunStep::withTokenCounts()->orderBy('id')->get()->keyBy('id');

        $this->assertSame(4580, $steps[10]->input_tokens);
        $this->assertSame(1729, $steps[10]->output_tokens);
        $this->assertSame(40522, $steps[20]->input_tokens);
    }

    public function test_a_step_whose_calls_were_never_counted_has_no_tokens_rather_than_zero(): void
    {
        $step = AiRunStep::withTokenCounts()->findOrFail(11);

        $this->assertNull($step->input_tokens);
        $this->assertNull($step->output_tokens);
    }

    public function test_a_run_adds_up_the_calls_of_all_its_steps_and_no_other_run(): void
    {
        $runs = AiRun::withoutWorkspace()->withTokenCounts()->orderBy('id')->get()->keyBy('id');

        $this->assertSame(4580, $runs[1]->input_tokens);
        $this->assertSame(1729, $runs[1]->output_tokens);
        $this->assertSame(40522, $runs[2]->input_tokens);
        $this->assertSame(1829, $runs[2]->output_tokens);
    }

    public function test_a_run_without_steps_has_no_tokens(): void
    {
        $this->insertRun(3, workspaceId: 7);

        $run = AiRun::withoutWorkspace()->withTokenCounts()->findOrFail(3);

        $this->assertNull($run->input_tokens);
        $this->assertNull($run->output_tokens);
    }

    public function test_the_counts_are_left_out_of_a_step_loaded_without_them(): void
    {
        $this->assertArrayNotHasKey('input_tokens', AiRunStep::findOrFail(10)->getAttributes());
    }

    protected function insertRun(int $id, int $workspaceId): void
    {
        DB::table('genie_ai_runs')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'workspace_id' => $workspaceId,
        ]);
    }

    protected function insertStep(int $id, int $runId, int $position): void
    {
        DB::table('genie_ai_run_steps')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'run_id' => $runId,
            'position' => $position,
        ]);
    }

    protected function insertEntry(?int $runStepId, ?int $input, ?int $output): void
    {
        DB::table('genie_logs')->insert([
            'uuid' => (string) Str::uuid(),
            'type' => GenieType::RUN_RESPONSE->value,
            'action' => GenieSyncAction::CREATE->value,
            'run_step_id' => $runStepId,
            'input_tokens' => $input,
            'output_tokens' => $output,
        ]);
    }
}
