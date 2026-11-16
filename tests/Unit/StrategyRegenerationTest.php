<?php

namespace Tests\Unit;

use App\Enums\BriefingStatus;
use App\Enums\RunStatus;
use App\Enums\StrategyRegenerationMode;
use App\Enums\StrategyStatus;
use App\Genie\Strategy\StrategyRegeneration;
use App\Genie\Strategy\StrategyRegenerationException;
use App\Models\AiRun;
use App\Models\Briefing;
use App\Models\Strategy;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * An admin's regeneration request keeps what the workspace had and leaves an empty strategy
 * waiting for the customer. The suite has no migrated database, so the tables are built in memory;
 * no workspace is current, as in the admin panel.
 */
class StrategyRegenerationTest extends TestCase
{
    protected Workspace $workspace;

    protected StrategyRegeneration $regeneration;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.strategy_regeneration', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'strategy_regeneration');

        Schema::create('genie_strategies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->json('content')->nullable();
            $table->tinyInteger('status');
            $table->timestamps();
        });

        Schema::create('genie_briefings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->json('content')->nullable();
            $table->unsignedBigInteger('version_id');
            $table->tinyInteger('status');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('genie_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->unsignedBigInteger('briefing_id')->nullable();
            $table->unsignedBigInteger('strategy_id')->nullable();
            $table->json('parameters')->nullable();
            $table->uuid('conversation_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('genie_workspaces_versions', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('version_id');
            $table->timestamps();
        });

        $this->workspace = $this->workspace(1, versionId: 9);
        $this->regeneration = new StrategyRegeneration;
    }

    public function test_the_previous_strategy_is_kept_and_an_empty_one_is_left_waiting(): void
    {
        $previous = $this->strategy(StrategyStatus::APPROVED, withRun: true, content: ['big_idea' => 'Kept']);

        $waiting = $this->regeneration->request($this->workspace, StrategyRegenerationMode::STRATEGY);

        $this->assertNotSame($previous->id, $waiting->id);
        $this->assertSame(StrategyStatus::OPEN, $waiting->status);
        $this->assertTrue($waiting->awaitsGeneration());

        $previous->refresh();
        $this->assertSame(StrategyStatus::APPROVED, $previous->status);
        $this->assertSame(['big_idea' => 'Kept'], $previous->content);

        $this->assertSame(0, Briefing::withoutWorkspace()->count());
    }

    public function test_asking_again_reuses_the_strategy_already_waiting(): void
    {
        $this->strategy(StrategyStatus::APPROVED, withRun: true);

        $first = $this->regeneration->request($this->workspace, StrategyRegenerationMode::STRATEGY);
        $second = $this->regeneration->request($this->workspace, StrategyRegenerationMode::NEW_BRIEFING);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(2, Strategy::withoutWorkspace()->count());
        $this->assertSame(1, Briefing::withoutWorkspace()->count());
    }

    public function test_a_workspace_that_never_had_a_strategy_gets_one_waiting(): void
    {
        $waiting = $this->regeneration->request($this->workspace, StrategyRegenerationMode::STRATEGY);

        $this->assertTrue($waiting->awaitsGeneration());
        $this->assertSame(1, Strategy::withoutWorkspace()->count());
    }

    /**
     * @return array<string, array{0: StrategyStatus}>
     */
    public static function unsettledStatuses(): array
    {
        return [
            'queued' => [StrategyStatus::OPEN],
            'running' => [StrategyStatus::RUNNING],
            'waiting for a step review' => [StrategyStatus::PENDING_REVIEW],
            'resumed after a step review' => [StrategyStatus::REVIEWED],
        ];
    }

    #[DataProvider('unsettledStatuses')]
    public function test_a_strategy_still_being_generated_cannot_be_regenerated(StrategyStatus $status): void
    {
        $this->strategy($status, withRun: true);

        try {
            $this->regeneration->request($this->workspace, StrategyRegenerationMode::NEW_BRIEFING);
            $this->fail('A strategy still being generated was regenerated.');
        } catch (StrategyRegenerationException $exception) {
            $this->assertSame('strategy_regeneration_generating', $exception->key);
        }

        $this->assertSame(1, Strategy::withoutWorkspace()->count());
        $this->assertSame(0, Briefing::withoutWorkspace()->count());
    }

    /**
     * @return array<string, array{0: StrategyStatus}>
     */
    public static function settledStatuses(): array
    {
        return [
            'failed' => [StrategyStatus::ERROR],
            'waiting for approval' => [StrategyStatus::PENDING_APPROVAL],
            'approved' => [StrategyStatus::APPROVED],
        ];
    }

    #[DataProvider('settledStatuses')]
    public function test_a_settled_strategy_can_be_regenerated(StrategyStatus $status): void
    {
        $previous = $this->strategy($status, withRun: true);

        $waiting = $this->regeneration->request($this->workspace, StrategyRegenerationMode::STRATEGY);

        $this->assertNotSame($previous->id, $waiting->id);
        $this->assertSame($status, $previous->refresh()->status);
    }

    public function test_a_new_briefing_is_an_empty_draft_on_the_current_version(): void
    {
        $previous = $this->briefing(['company' => 'Acme'], versionId: 4);

        $this->regeneration->request($this->workspace, StrategyRegenerationMode::NEW_BRIEFING);

        $latest = $this->latestBriefing();

        $this->assertNotSame($previous->id, $latest->id);
        $this->assertNull($latest->content);
        $this->assertSame(9, $latest->version_id);
        $this->assertSame(BriefingStatus::DRAFT, $latest->status);
        $this->assertSame($this->workspace->id, $latest->workspace_id);

        $previous->refresh();
        $this->assertSame(['company' => 'Acme'], $previous->content);
        $this->assertSame(BriefingStatus::COMPLETE, $previous->status);
    }

    public function test_a_cloned_briefing_copies_the_answers_into_a_draft_on_the_current_version(): void
    {
        $answers = ['company' => 'Acme', 'channels' => ['instagram', null], 'logo' => ['id' => 'a.png', 'path' => '/a.png']];
        $previous = $this->briefing($answers, versionId: 4);

        $this->regeneration->request($this->workspace, StrategyRegenerationMode::CLONED_BRIEFING);

        $latest = $this->latestBriefing();

        $this->assertNotSame($previous->id, $latest->id);
        $this->assertSame($answers, $latest->content);
        $this->assertSame(9, $latest->version_id);
        $this->assertSame(BriefingStatus::DRAFT, $latest->status);
        $this->assertSame(BriefingStatus::COMPLETE, $previous->refresh()->status);
    }

    public function test_cloning_without_a_briefing_is_refused(): void
    {
        try {
            $this->regeneration->request($this->workspace, StrategyRegenerationMode::CLONED_BRIEFING);
            $this->fail('A missing briefing was cloned.');
        } catch (StrategyRegenerationException $exception) {
            $this->assertSame('strategy_regeneration_no_briefing', $exception->key);
        }

        $this->assertSame(0, Strategy::withoutWorkspace()->count());
    }

    public function test_only_the_workspace_own_strategy_and_briefing_are_read(): void
    {
        $other = $this->workspace(2, versionId: 5);
        $this->strategy(StrategyStatus::RUNNING, withRun: true, workspace: $other);
        $this->briefing(['company' => 'Other'], versionId: 5, workspace: $other);

        $this->assertSame(['state' => 'none', 'has_briefing' => false], $this->regeneration->state($this->workspace));

        try {
            $this->regeneration->request($this->workspace, StrategyRegenerationMode::CLONED_BRIEFING);
            $this->fail('Another workspace\'s briefing was cloned.');
        } catch (StrategyRegenerationException $exception) {
            $this->assertSame('strategy_regeneration_no_briefing', $exception->key);
        }

        $waiting = $this->regeneration->request($this->workspace, StrategyRegenerationMode::NEW_BRIEFING);

        $this->assertSame($this->workspace->id, $waiting->workspace_id);
        $this->assertSame(9, $this->latestBriefing()->version_id);
    }

    public function test_state_follows_the_latest_strategy(): void
    {
        $this->assertSame('none', $this->regeneration->state($this->workspace)['state']);

        $strategy = $this->strategy(StrategyStatus::RUNNING, withRun: true);
        $this->assertSame('generating', $this->regeneration->state($this->workspace)['state']);

        $strategy->update(['status' => StrategyStatus::PENDING_APPROVAL]);
        $this->assertSame('pending_approval', $this->regeneration->state($this->workspace)['state']);

        $strategy->update(['status' => StrategyStatus::ERROR]);
        $this->assertSame('error', $this->regeneration->state($this->workspace)['state']);

        $strategy->update(['status' => StrategyStatus::APPROVED]);
        $this->assertSame('approved', $this->regeneration->state($this->workspace)['state']);

        $this->regeneration->request($this->workspace, StrategyRegenerationMode::STRATEGY);
        $this->assertSame('awaiting_generation', $this->regeneration->state($this->workspace)['state']);
    }

    public function test_state_says_whether_there_is_a_briefing_to_clone(): void
    {
        $this->assertFalse($this->regeneration->state($this->workspace)['has_briefing']);

        $this->briefing(['company' => 'Acme'], versionId: 9);

        $this->assertTrue($this->regeneration->state($this->workspace)['has_briefing']);
    }

    public function test_a_strategy_run_is_the_first_run_pointing_at_the_strategy(): void
    {
        $strategy = $this->strategy(StrategyStatus::APPROVED, withRun: true);
        $strategyRun = AiRun::withoutWorkspace()->where('strategy_id', $strategy->id)->sole();

        // An ideas run built from the approved strategy carries it too.
        $this->runFor($strategy);

        $this->assertSame($strategyRun->id, $strategy->aiRun()->withoutWorkspace()->first()->id);
        $this->assertSame(
            $strategyRun->id,
            Strategy::withoutWorkspace()->with(['aiRun' => fn ($run) => $run->withoutWorkspace()])->find($strategy->id)->aiRun->id,
        );
    }

    public function test_ideas_read_the_newest_approved_strategy(): void
    {
        $older = $this->strategy(StrategyStatus::APPROVED, withRun: true);
        $newest = $this->strategy(StrategyStatus::APPROVED, withRun: true);
        $this->strategy(StrategyStatus::PENDING_APPROVAL, withRun: true);
        $this->regeneration->request($this->workspace, StrategyRegenerationMode::STRATEGY);

        $approved = Strategy::byWorkspace($this->workspace)->approved()->latest('id')->first();

        $this->assertNotSame($older->id, $approved->id);
        $this->assertSame($newest->id, $approved->id);
    }

    public function test_awaiting_and_generating_read_a_loaded_run(): void
    {
        $open = (new Strategy)->forceFill(['status' => StrategyStatus::OPEN])->setRelation('aiRun', null);
        $this->assertTrue($open->awaitsGeneration());
        $this->assertFalse($open->isGenerating());

        $queued = (new Strategy)->forceFill(['status' => StrategyStatus::OPEN])->setRelation('aiRun', new AiRun);
        $this->assertFalse($queued->awaitsGeneration());
        $this->assertTrue($queued->isGenerating());

        $legacy = (new Strategy)->forceFill(['status' => StrategyStatus::RUNNING])->setRelation('aiRun', null);
        $this->assertFalse($legacy->awaitsGeneration());
        $this->assertFalse($legacy->isGenerating());
    }

    public function test_modes_say_what_they_do_with_the_briefing(): void
    {
        $this->assertFalse(StrategyRegenerationMode::STRATEGY->createsBriefing());
        $this->assertTrue(StrategyRegenerationMode::NEW_BRIEFING->createsBriefing());
        $this->assertTrue(StrategyRegenerationMode::CLONED_BRIEFING->createsBriefing());

        $this->assertFalse(StrategyRegenerationMode::NEW_BRIEFING->clonesBriefing());
        $this->assertTrue(StrategyRegenerationMode::CLONED_BRIEFING->clonesBriefing());
    }

    protected function workspace(int $id, int $versionId): Workspace
    {
        DB::table('genie_workspaces_versions')->insert(['workspace_id' => $id, 'version_id' => $versionId]);

        return (new Workspace)->forceFill(['id' => $id]);
    }

    /**
     * @param  array<string, mixed>|null  $content
     */
    protected function strategy(
        StrategyStatus $status,
        bool $withRun = false,
        ?array $content = null,
        ?Workspace $workspace = null,
    ): Strategy {
        $strategy = Strategy::create([
            'workspace_id' => ($workspace ?? $this->workspace)->id,
            'content' => $content,
            'status' => $status,
        ]);

        if ($withRun) {
            $this->runFor($strategy);
        }

        return $strategy;
    }

    protected function runFor(Strategy $strategy): AiRun
    {
        return AiRun::create([
            'workspace_id' => $strategy->workspace_id,
            'strategy_id' => $strategy->id,
            'status' => RunStatus::COMPLETE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    protected function briefing(array $content, int $versionId, ?Workspace $workspace = null): Briefing
    {
        $briefing = new Briefing([
            'content' => $content,
            'version_id' => $versionId,
            'status' => BriefingStatus::COMPLETE,
        ]);

        $briefing->workspace_id = ($workspace ?? $this->workspace)->id;
        $briefing->save();

        return $briefing;
    }

    protected function latestBriefing(): Briefing
    {
        return Briefing::byWorkspace($this->workspace)->latest('id')->firstOrFail();
    }
}
