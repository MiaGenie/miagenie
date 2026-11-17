<?php

namespace Tests\Unit;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Ai;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClassConstant;
use Tests\TestCase;

/**
 * Migration 0054 gives each tiered profile whose model laravel/ai 1.4 changed a copy pinned to
 * the 0.11.0 model. The suite has no migrated database, so the profiles table is built in memory.
 */
class PinnedModelProfilesMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.pinned_profiles', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'pinned_profiles');

        Schema::create('genie_model_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('provider');
            $table->string('model_tier')->default('default');
            $table->string('model')->nullable();
            $table->integer('timeout')->nullable();
            $table->integer('position');
            $table->timestamps();
        });
    }

    public function test_a_tiered_profile_whose_model_changed_gets_a_pinned_copy(): void
    {
        $this->profile('OpenAi - default', 'openai', 'default', position: 1, timeout: 240);
        $this->profile('Claude - smartest', 'anthropic', 'smartest', position: 2);

        $this->migrate();

        $this->assertSame([
            ['name' => 'OpenAi - default (gpt-5.4)', 'provider' => 'openai', 'model_tier' => 'other', 'model' => 'gpt-5.4', 'timeout' => 240, 'position' => 3],
            ['name' => 'Claude - smartest (claude-opus-4-8)', 'provider' => 'anthropic', 'model_tier' => 'other', 'model' => 'claude-opus-4-8', 'timeout' => null, 'position' => 4],
        ], $this->copies());
    }

    public function test_the_original_profiles_are_left_as_they_were(): void
    {
        $id = $this->profile('OpenAi - default', 'openai', 'default', position: 1);

        $this->migrate();

        $original = DB::table('genie_model_profiles')->find($id);
        $this->assertSame('default', $original->model_tier);
        $this->assertNull($original->model);
        $this->assertSame('OpenAi - default', $original->name);
    }

    public function test_profiles_whose_model_did_not_change_get_no_copy(): void
    {
        $this->profile('Grok - cheapest', 'xai', 'cheapest', position: 1);
        $this->profile('Router - cheapest', 'openrouter', 'cheapest', position: 2);
        $this->profile('Groq - default', 'groq', 'default', position: 3);
        $this->profile('Azure - default', 'azure', 'default', position: 4);
        $this->profile('Local - default', 'my-llm', 'default', position: 5);
        $this->profile('OpenAi - pinned', 'openai', 'other', position: 6, model: 'gpt-5.2');

        $this->migrate();

        $this->assertSame([], $this->copies());
        $this->assertSame(6, DB::table('genie_model_profiles')->count());
    }

    public function test_a_tier_pinned_in_config_gets_no_copy(): void
    {
        config()->set('ai.providers.gemini.models.text.default', 'gemini-3.7-flash');

        $this->profile('Gemini - default', 'gemini', 'default', position: 1);
        $this->profile('Gemini - cheapest', 'gemini', 'cheapest', position: 2);

        $this->migrate();

        $this->assertSame(['Gemini - cheapest (gemini-3.5-flash-lite)'], array_column($this->copies(), 'name'));
    }

    public function test_running_it_again_adds_no_second_copy(): void
    {
        $this->profile('OpenAi - default', 'openai', 'default', position: 1);

        $this->migrate();
        $this->migrate();

        $this->assertSame(['OpenAi - default (gpt-5.4)'], array_column($this->copies(), 'name'));
    }

    public function test_a_provider_configured_under_another_name_is_matched_by_its_driver(): void
    {
        config()->set('ai.providers.openai-eu', ['driver' => 'openai', 'key' => 'test']);

        $this->profile('OpenAi EU - cheapest', 'openai-eu', 'cheapest', position: 1);

        $this->migrate();

        $this->assertSame([
            ['name' => 'OpenAi EU - cheapest (gpt-5.4-nano)', 'provider' => 'openai-eu', 'model_tier' => 'other', 'model' => 'gpt-5.4-nano', 'timeout' => null, 'position' => 2],
        ], $this->copies());
    }

    public function test_a_long_name_is_shortened_to_keep_the_pinned_model(): void
    {
        $this->profile(str_repeat('é', 255), 'openai', 'default', position: 1);

        $this->migrate();

        $name = $this->copies()[0]['name'];
        $this->assertSame(255, mb_strlen($name));
        $this->assertStringEndsWith(' (gpt-5.4)', $name);
    }

    /**
     * Guards the hand-written map against the installed SDK: every listed tier must now resolve to
     * another model, or its copy would pin nothing new.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function previousModels(): array
    {
        // Data providers run before the application boots, so database_path() is not available.
        $migration = require dirname(__DIR__, 2).'/database/migrations/0054_clone_model_profiles_pinned_to_previous_models.php';
        $map = (new ReflectionClassConstant($migration, 'PREVIOUS_MODELS'))->getValue();

        $cases = [];

        foreach ($map as $driver => $tiers) {
            foreach ($tiers as $tier => $model) {
                $cases["{$driver} {$tier}"] = [$driver, $tier, $model];
            }
        }

        return $cases;
    }

    #[DataProvider('previousModels')]
    public function test_each_listed_tier_now_resolves_to_another_model(string $driver, string $tier, string $previous): void
    {
        $this->assertNotSame($previous, Ai::textProvider($driver)->{"{$tier}TextModel"}());
    }

    public function test_the_tiers_left_out_still_resolve_to_their_previous_model(): void
    {
        $this->assertSame('anthropic/claude-haiku-4.5', Ai::textProvider('openrouter')->cheapestTextModel());
        $this->assertSame('grok-4.20-non-reasoning', Ai::textProvider('xai')->cheapestTextModel());
        $this->assertSame('openai/gpt-oss-120b', Ai::textProvider('groq')->defaultTextModel());
        $this->assertSame('openai/gpt-oss-20b', Ai::textProvider('groq')->cheapestTextModel());
        $this->assertSame('openai/gpt-oss-120b', Ai::textProvider('groq')->smartestTextModel());
    }

    protected function migrate(): void
    {
        /** @var Migration $migration */
        $migration = require database_path('migrations/0054_clone_model_profiles_pinned_to_previous_models.php');

        $migration->up();
    }

    protected function profile(string $name, string $provider, string $tier, int $position, ?int $timeout = null, ?string $model = null): int
    {
        return DB::table('genie_model_profiles')->insertGetId([
            'uuid' => fake()->uuid(),
            'name' => $name,
            'provider' => $provider,
            'model_tier' => $tier,
            'model' => $model,
            'timeout' => $timeout,
            'position' => $position,
        ]);
    }

    /**
     * The rows the migration wrote: it stamps them, while the fixtures above are inserted bare.
     *
     * @return list<array{name: string, provider: string, model_tier: string, model: string, timeout: int|null, position: int}>
     */
    protected function copies(): array
    {
        return DB::table('genie_model_profiles')
            ->where('model_tier', 'other')
            ->whereNotNull('created_at')
            ->orderBy('position')
            ->get(['name', 'provider', 'model_tier', 'model', 'timeout', 'position'])
            ->map(fn (object $row): array => [
                'name' => $row->name,
                'provider' => $row->provider,
                'model_tier' => $row->model_tier,
                'model' => $row->model,
                'timeout' => $row->timeout === null ? null : (int) $row->timeout,
                'position' => (int) $row->position,
            ])
            ->all();
    }
}
