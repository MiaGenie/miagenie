<?php

namespace App\Ai;

use App\Ai\Agents\DefinedSchemaStepAgent;
use App\Ai\Agents\StepAgent;
use App\Ai\Agents\StructuredStepAgent;
use App\Concerns\ConfiguresAiProvider;
use App\Configs\OpenAIConfig;
use App\Genie\Runs\SchemaDefinition;
use App\Genie\Schema\StepSchemaBuilder;
use App\Models\ModelProfile;
use App\Models\RuleStep;
use Laravel\Ai\Enums\Lab;
use RuntimeException;

class StepAgentFactory
{
    use ConfiguresAiProvider;

    /**
     * Seconds, used when neither the profile nor the admin config specifies one.
     */
    public const DEFAULT_TIMEOUT = 300;

    /**
     * Providers the SDK can generate images with, as `ModelProfile::$provider` values.
     *
     * @var list<string>
     */
    public const IMAGE_PROVIDERS = ['openai', 'azure', 'gemini', 'xai', 'openrouter', 'bedrock'];

    /**
     * Providers whose text models can search the web, as `ModelProfile::$provider` values: the SDK
     * providers implementing SupportsWebSearch. Any other provider refuses the WebSearch tool when
     * the call is made.
     *
     * @var list<string>
     */
    public const WEB_SEARCH_PROVIDERS = ['openai', 'azure', 'anthropic', 'gemini', 'xai', 'openrouter'];

    public function __construct(protected StepSchemaBuilder $schemas = new StepSchemaBuilder) {}

    /**
     * Build the agent for a step and make sure its provider is credentialed.
     */
    public function make(RuleStep $step, string $locale): StepAgent
    {
        $this->prepare($step);

        return $this->hasSchema($step)
            ? new StructuredStepAgent($step, $locale)
            : new StepAgent($step, $locale);
    }

    /**
     * Build a plain-text agent for a step, whatever its response_format says, and make sure its
     * provider is credentialed.
     *
     * For a context step: it primes the conversation and produces nothing to store, so asking it
     * for structured output would only force a shape onto prose.
     */
    public function makeText(RuleStep $step, string $locale): StepAgent
    {
        $this->prepare($step);

        return new StepAgent($step, $locale);
    }

    /**
     * Build the agent for a step whose schema the run type states in code, and make sure its
     * provider is credentialed.
     *
     * The step's own response_format is not consulted: a run type that hands over a definition
     * has decided the step returns structured output.
     */
    public function makeDefined(RuleStep $step, string $locale, SchemaDefinition $definition): StepAgent
    {
        $this->prepare($step);

        return new DefinedSchemaStepAgent($step, $locale, $definition);
    }

    /**
     * A step asks for structured output only when it declares json_schema *and* a schema can
     * actually be resolved — an empty stored schema falls back to a plain text prompt rather
     * than sending an invalid contract.
     */
    public function hasSchema(RuleStep $step, ?string $locale = null): bool
    {
        return $this->schemas->closureForStep($step, $locale) !== null;
    }

    /**
     * Resolve the step's provider and make sure it is credentialed, for a call that goes to the
     * SDK directly rather than through an agent — image generation.
     */
    public function prepare(RuleStep $step): Lab|string
    {
        $provider = $this->provider($step);

        $this->configureAiProvider($provider);

        return $provider;
    }

    /**
     * The image model a step names outright, or null to let the provider pick its default.
     *
     * Image providers have a single tier, so a profile on DEFAULT, CHEAPEST or SMARTEST all mean
     * the provider's one image model; only OTHER carries a model name of its own.
     */
    public function imageModel(RuleStep $step): ?string
    {
        return $step->modelProfile?->explicitModel();
    }

    /**
     * Whether the profile's provider can generate images at all.
     */
    public static function supportsImages(ModelProfile|RuleStep $subject): bool
    {
        $profile = $subject instanceof RuleStep ? $subject->modelProfile : $subject;

        return $profile !== null && in_array($profile->provider, self::IMAGE_PROVIDERS, true);
    }

    /**
     * Whether the profile's provider can search the web.
     */
    public static function supportsWebSearch(ModelProfile|RuleStep $subject): bool
    {
        $profile = $subject instanceof RuleStep ? $subject->modelProfile : $subject;

        return $profile !== null && in_array($profile->provider, self::WEB_SEARCH_PROVIDERS, true);
    }

    public function provider(RuleStep $step): Lab|string
    {
        $profile = $step->modelProfile;

        if (! $profile) {
            throw new RuntimeException("Step [{$step->id}] has no model profile.");
        }

        return $profile->lab();
    }

    /**
     * The per-call HTTP timeout.
     *
     * The SDK defaults to 60 seconds, which is below what a reasoning model needs, so a
     * profile without an explicit value falls back to the admin-configured request timeout
     * and finally to a workable default rather than the SDK's.
     */
    public function timeout(RuleStep $step): int
    {
        $configured = (int) (app(OpenAIConfig::class)->all()['request_timeout'] ?? 0);

        return $step->modelProfile?->timeout
            ?: ($configured > 0 ? $configured : self::DEFAULT_TIMEOUT);
    }
}
