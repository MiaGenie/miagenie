<?php

namespace Tests\Unit;

use App\Concerns\Requests\ValidatesModelProfile;
use App\Enums\ModelTier;
use App\Http\Requests\Admin\StoreModelProfile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * A profile can only be put on a provider config/ai.php configures, which is what the SDK resolves
 * it against: not Bedrock (its aws/aws-sdk-php is not installed) nor TypeSafe (classification only),
 * but an openai-compatible instance configured under its own name, yes.
 */
class ValidatesModelProfileTest extends TestCase
{
    public function test_only_configured_providers_are_offered(): void
    {
        $providers = array_column(ValidatesModelProfile::providerOptions(), 'value');

        $this->assertNotContains('bedrock', $providers);
        $this->assertNotContains('typesafe', $providers);
        $this->assertContains('openai', $providers);
        $this->assertContains('deepseek', $providers);
        $this->assertContains('openai-compatible', $providers);
    }

    public function test_a_profile_on_a_provider_that_is_not_configured_is_refused(): void
    {
        foreach (['bedrock', 'typesafe', 'not-configured-llm'] as $provider) {
            $validator = $this->validate(['provider' => $provider]);

            $this->assertTrue($validator->fails(), $provider);
            $this->assertArrayHasKey('provider', $validator->errors()->toArray(), $provider);
        }
    }

    public function test_a_profile_on_an_sdk_provider_is_accepted(): void
    {
        $this->assertFalse($this->validate(['provider' => 'anthropic'])->fails());
    }

    public function test_a_profile_on_a_provider_named_in_config_is_accepted(): void
    {
        config()->set('ai.providers.my-local-llm', ['driver' => 'openai-compatible', 'url' => 'http://localhost:1234/v1']);

        $this->assertFalse($this->validate(['provider' => 'my-local-llm'])->fails());
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function validate(array $input): \Illuminate\Validation\Validator
    {
        return Validator::make(
            [...['name' => 'Profile', 'provider' => 'openai', 'model_tier' => ModelTier::DEFAULT->value], ...$input],
            (new StoreModelProfile)->rules(),
        );
    }
}
