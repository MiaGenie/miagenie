<?php

namespace App\Ai;

use App\Ai\Agents\StepAgent;
use App\Ai\Agents\StructuredStepAgent;
use App\Concerns\ConfiguresAiProvider;
use App\Configs\OpenAIConfig;
use App\Genie\Schema\StepSchemaBuilder;
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

    public function __construct(protected StepSchemaBuilder $schemas = new StepSchemaBuilder) {}

    /**
     * Build the agent for a step and make sure its provider is credentialed.
     */
    public function make(RuleStep $step, string $locale): StepAgent
    {
        $this->configureAiProvider($this->provider($step));

        return $this->hasSchema($step)
            ? new StructuredStepAgent($step, $locale)
            : new StepAgent($step, $locale);
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
