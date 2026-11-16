<?php

namespace Tests\Unit;

use App\Enums\RuleSubType;
use App\Http\Requests\Admin\StoreRuleStep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The web search rules, exercised through the real step form. The suite has no migrated database,
 * so the model profiles table the form checks against is built in memory.
 */
class ValidatesStepWebSearchTest extends TestCase
{
    protected const OPENAI_PROFILE = 1;

    protected const DEEPSEEK_PROFILE = 2;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.step_web_search', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'step_web_search');

        Schema::create('genie_model_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('name');
            $table->string('provider');
            $table->string('model_tier');
            $table->string('model')->nullable();
            $table->unsignedInteger('timeout')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        DB::table('genie_model_profiles')->insert([
            ['id' => self::OPENAI_PROFILE, 'uuid' => 'a', 'name' => 'OpenAI', 'provider' => 'openai', 'model_tier' => 'default'],
            ['id' => self::DEEPSEEK_PROFILE, 'uuid' => 'b', 'name' => 'DeepSeek', 'provider' => 'deepseek', 'model_tier' => 'default'],
        ]);
    }

    /**
     * A strategy step form that is valid apart from what a test overrides.
     *
     * @param  array<string, mixed>  $input
     */
    protected function request(array $input = []): StoreRuleStep
    {
        $request = StoreRuleStep::create('/', 'POST', array_merge([
            'rule_sub_type' => RuleSubType::BRIEFINGS->value,
            'response_format' => 'json_schema',
            'name' => 'Market research',
            'model_profile_id' => self::OPENAI_PROFILE,
            'message' => 'Research the market.',
            'requires_review' => false,
            'optional' => false,
        ], $input));

        $request->setContainer($this->app)->setRedirector($this->app['redirect']);

        return $request;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    protected function errors(array $input): array
    {
        try {
            $this->request($input)->validateResolved();
        } catch (ValidationException $exception) {
            return $exception->errors();
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{web_search: bool, web_search_max: int|null, web_search_domains: list<string>|null}
     */
    protected function attributes(array $input): array
    {
        $request = $this->request($input);
        $request->validateResolved();

        return (fn () => $this->webSearchAttributes())->call($request);
    }

    public function test_a_strategy_step_on_a_provider_that_searches_may_search(): void
    {
        $attributes = $this->attributes([
            'web_search' => true,
            'web_search_max' => '5',
            'web_search_domains' => "https://Statista.com/\nexample.org, example.org\n\n",
        ]);

        $this->assertSame([
            'web_search' => true,
            'web_search_max' => 5,
            'web_search_domains' => ['statista.com', 'example.org'],
        ], $attributes);
    }

    public function test_the_domains_may_be_sent_as_a_list(): void
    {
        $attributes = $this->attributes([
            'web_search' => true,
            'web_search_domains' => [' Insights.Example.com ', 'http://example.org'],
        ]);

        $this->assertSame(['insights.example.com', 'example.org'], $attributes['web_search_domains']);
    }

    public function test_a_search_without_a_cap_or_domains_stores_neither(): void
    {
        $this->assertSame([
            'web_search' => true,
            'web_search_max' => null,
            'web_search_domains' => null,
        ], $this->attributes(['web_search' => true]));
    }

    public function test_a_step_that_does_not_search_drops_its_cap_and_domains(): void
    {
        $this->assertSame([
            'web_search' => false,
            'web_search_max' => null,
            'web_search_domains' => null,
        ], $this->attributes([
            'web_search' => false,
            'web_search_max' => 5,
            'web_search_domains' => 'statista.com',
        ]));
    }

    public function test_a_form_without_the_switch_does_not_search(): void
    {
        $this->assertFalse($this->attributes([])['web_search']);
    }

    public function test_every_strategy_sub_type_may_search(): void
    {
        foreach ([RuleSubType::BRIEFINGS, RuleSubType::BRIEFINGS_MULTIPLE, RuleSubType::CHANNELS] as $subType) {
            $errors = $this->errors(['rule_sub_type' => $subType->value, 'web_search' => true]);

            $this->assertArrayNotHasKey('web_search', $errors, $subType->name);
        }
    }

    public function test_a_step_outside_the_strategy_cannot_search(): void
    {
        $errors = $this->errors(['rule_sub_type' => RuleSubType::POSTS_CONTENT->value, 'web_search' => true]);

        $this->assertSame([__('genie.step_web_search_not_supported')], $errors['web_search'] ?? null);
    }

    public function test_a_step_outside_the_strategy_may_still_be_saved_without_search(): void
    {
        $errors = $this->errors(['rule_sub_type' => RuleSubType::POSTS_CONTENT->value, 'web_search' => false]);

        $this->assertArrayNotHasKey('web_search', $errors);
    }

    public function test_a_provider_that_cannot_search_is_refused_for_a_searching_step(): void
    {
        $errors = $this->errors(['model_profile_id' => self::DEEPSEEK_PROFILE, 'web_search' => true]);

        $this->assertSame([__('genie.step_web_search_provider')], $errors['model_profile_id'] ?? null);
    }

    public function test_a_provider_that_cannot_search_is_fine_for_a_step_that_does_not(): void
    {
        $this->assertSame([], $this->errors(['model_profile_id' => self::DEEPSEEK_PROFILE, 'web_search' => false]));
    }

    public function test_a_domain_with_a_path_is_refused(): void
    {
        $errors = $this->errors(['web_search' => true, 'web_search_domains' => "statista.com\nexample.org/blog"]);

        $this->assertSame(
            [__('genie.step_web_search_domain', ['domain' => 'example.org/blog'])],
            $errors['web_search_domains.1'] ?? null,
        );
        $this->assertArrayNotHasKey('web_search_domains.0', $errors);
    }

    public function test_a_word_without_a_top_level_domain_is_refused(): void
    {
        $this->assertArrayHasKey('web_search_domains.0', $this->errors(['web_search' => true, 'web_search_domains' => 'localhost']));
    }

    public function test_the_searches_cap_is_bounded(): void
    {
        $this->assertArrayHasKey('web_search_max', $this->errors(['web_search' => true, 'web_search_max' => 0]));
        $this->assertArrayHasKey('web_search_max', $this->errors(['web_search' => true, 'web_search_max' => StoreRuleStep::MAX_WEB_SEARCHES + 1]));
        $this->assertArrayNotHasKey('web_search_max', $this->errors(['web_search' => true, 'web_search_max' => StoreRuleStep::MAX_WEB_SEARCHES]));
    }

    public function test_the_number_of_domains_is_bounded(): void
    {
        $domains = collect(range(1, StoreRuleStep::MAX_WEB_SEARCH_DOMAINS + 1))->map(fn (int $n) => "site{$n}.com")->all();

        $this->assertArrayHasKey('web_search_domains', $this->errors(['web_search' => true, 'web_search_domains' => $domains]));
    }
}
