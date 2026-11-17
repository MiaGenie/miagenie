<?php

namespace Tests\Unit;

use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Http\Controllers\Admin\LogsController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Response;
use Tests\TestCase;

/**
 * The admin log list stays light — no request/response payloads, ordered by id — and the detail page
 * loads the one entry asked for. The suite has no migrated database, so the tables are built in memory.
 */
class LogsControllerTest extends TestCase
{
    protected LogsController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.admin_logs', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'admin_logs');

        Schema::create('genie_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type');
            $table->string('action');
            $table->unsignedBigInteger('run_step_id')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->smallInteger('duration')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamps();
        });

        Schema::create('genie_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('workspace_id');
            $table->timestamps();
        });

        Schema::create('genie_ai_run_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('run_id');
            $table->unsignedBigInteger('step_id')->nullable();
            $table->unsignedInteger('position');
            $table->timestamps();
        });

        Schema::create('genie_rule_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $this->controller = new LogsController;
    }

    public function test_the_list_is_newest_first_by_id(): void
    {
        // Ids and timestamps disagree on purpose: the list follows the primary key.
        $this->log(1, createdAt: '2026-11-16 10:00:00');
        $this->log(2, createdAt: '2026-11-16 09:00:00');
        $this->log(3, createdAt: '2026-11-16 11:00:00');

        $props = $this->props($this->controller->index($this->request('/genie/admin/logs')), 'Genie/Admin/Logs/Index');

        $this->assertSame([3, 2, 1], array_column($props['records']['data'], 'id'));
        $this->assertSame(3, $props['records']['meta']['total']);
        $this->assertNull($props['runStep']);
    }

    public function test_the_list_leaves_out_the_request_and_response_payloads(): void
    {
        $this->log(1, request: ['prompt' => 'A long prompt'], response: ['file' => 'abc']);

        $props = $this->props($this->controller->index($this->request('/genie/admin/logs')), 'Genie/Admin/Logs/Index');

        $record = $props['records']['data'][0];
        $this->assertArrayNotHasKey('request', $record);
        $this->assertArrayNotHasKey('response', $record);
        $this->assertSame(GenieType::RUN_RESPONSE->value, $record['type']);
        $this->assertSame(GenieSyncAction::CREATE->value, $record['action']);
        $this->assertSame('2026-11-16 10:00:00', $record['created_at']);
    }

    public function test_the_list_carries_the_tokens_of_each_call(): void
    {
        $this->log(1, inputTokens: 2280, outputTokens: 829);
        $this->log(2);

        $props = $this->props($this->controller->index($this->request('/genie/admin/logs')), 'Genie/Admin/Logs/Index');

        [$uncounted, $counted] = $props['records']['data'];
        $this->assertSame([2280, 829], [$counted['input_tokens'], $counted['output_tokens']]);
        $this->assertSame([null, null], [$uncounted['input_tokens'], $uncounted['output_tokens']]);
    }

    public function test_the_list_names_actions_from_the_sync_actions_not_the_sync_statuses(): void
    {
        $this->log(1);

        $props = $this->props($this->controller->index($this->request('/genie/admin/logs')), 'Genie/Admin/Logs/Index');

        $this->assertArrayNotHasKey('genieSyncStatus', $props);
        $this->assertSame(
            array_column(GenieSyncAction::cases(), 'name', 'value'),
            array_column($props['genieSyncActions'], 'name', 'value')
        );
        $this->assertSame('CREATE', array_column($props['genieSyncActions'], 'name', 'value')[$props['records']['data'][0]['action']]);
    }

    public function test_the_list_can_be_narrowed_to_one_run_step(): void
    {
        DB::table('genie_ai_runs')->insert(['id' => 7, 'uuid' => $runUuid = (string) Str::uuid(), 'workspace_id' => 1]);
        DB::table('genie_rule_steps')->insert(['id' => 4, 'uuid' => (string) Str::uuid(), 'name' => 'Image']);
        DB::table('genie_ai_run_steps')->insert([
            ['id' => 11, 'uuid' => $stepUuid = (string) Str::uuid(), 'run_id' => 7, 'step_id' => 4, 'position' => 2],
            ['id' => 12, 'uuid' => (string) Str::uuid(), 'run_id' => 7, 'step_id' => 4, 'position' => 3],
        ]);

        $this->log(1, runStepId: 11);
        $this->log(2, runStepId: 12);
        $this->log(3, runStepId: 11);
        $this->log(4);

        $props = $this->props(
            $this->controller->index($this->request('/genie/admin/logs', ['run_step' => $stepUuid])),
            'Genie/Admin/Logs/Index'
        );

        $this->assertSame([3, 1], array_column($props['records']['data'], 'id'));
        $this->assertSame(['run_step' => $stepUuid], $props['filter']);
        $this->assertSame(
            ['id' => $stepUuid, 'run' => $runUuid, 'position' => 2, 'name' => 'Image'],
            $props['runStep']
        );
    }

    public function test_the_detail_page_shows_the_requested_entry_with_its_payloads(): void
    {
        $this->log(1);
        $this->log(2, request: ['prompt' => 'Draw a cat'], response: ['file' => 'abc'], duration: 42, inputTokens: 120, outputTokens: 4000);

        $props = $this->props($this->controller->view($this->request('/genie/admin/logs/2', log: 2)), 'Genie/Admin/Logs/View');

        $this->assertSame(2, $props['log']['id']);
        $this->assertSame(['prompt' => 'Draw a cat'], $props['log']['request']);
        $this->assertSame(['file' => 'abc'], $props['log']['response']);
        $this->assertSame(42, $props['log']['duration']);
        $this->assertSame(120, $props['log']['input_tokens']);
        $this->assertSame(4000, $props['log']['output_tokens']);
        $this->assertSame('RUN_RESPONSE', $props['logType']);
        $this->assertSame('CREATE', $props['logAction']);
    }

    public function test_the_detail_page_of_an_unknown_entry_is_not_found(): void
    {
        $this->log(1);

        $this->expectException(ModelNotFoundException::class);

        $this->controller->view($this->request('/genie/admin/logs/99', log: 99));
    }

    /**
     * @param  array<string, mixed>|null  $request
     * @param  array<string, mixed>|null  $response
     */
    protected function log(
        int $id,
        ?int $runStepId = null,
        ?array $request = null,
        ?array $response = null,
        int $duration = 10,
        string $createdAt = '2026-11-16 10:00:00',
        ?int $inputTokens = null,
        ?int $outputTokens = null,
    ): void {
        DB::table('genie_logs')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'type' => GenieType::RUN_RESPONSE->value,
            'action' => GenieSyncAction::CREATE->value,
            'run_step_id' => $runStepId,
            'request' => $request === null ? null : json_encode($request),
            'response' => $response === null ? null : json_encode($response),
            'duration' => $duration,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * An Inertia (XHR) request, bound to a `logs/{log}` route when a log id is given.
     *
     * @param  array<string, string>  $query
     */
    protected function request(string $uri, array $query = [], ?int $log = null): Request
    {
        $request = Request::create($uri, 'GET', $query, [], [], ['HTTP_X_INERTIA' => 'true']);

        if ($log !== null) {
            $route = (new Route('GET', 'genie/admin/logs/{log}', []))->bind($request);
            $request->setRouteResolver(fn () => $route);
        }

        $this->app->instance('request', $request);

        return $request;
    }

    /**
     * The page props an Inertia response would send to the browser, after checking which page it renders.
     *
     * @return array<string, mixed>
     */
    protected function props(Response $page, string $component): array
    {
        $payload = $page->toResponse(request())->getData(true);

        $this->assertSame($component, $payload['component']);

        return $payload['props'];
    }
}
