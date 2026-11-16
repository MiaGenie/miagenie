<?php

namespace Tests\Unit;

use App\Concerns\Requests\ValidatesModelProfile;
use App\Enums\ModelTier;
use App\Http\Requests\Admin\StoreModelProfile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Bedrock needs aws/aws-sdk-php, which the application does not install, so a profile cannot be
 * put on it, while a provider configured by name in config/ai.php still can.
 */
class ValidatesModelProfileTest extends TestCase
{
    public function test_bedrock_is_not_offered_as_a_profile_provider(): void
    {
        $providers = array_column(ValidatesModelProfile::providerOptions(), 'value');

        $this->assertNotContains('bedrock', $providers);
        $this->assertContains('openai', $providers);
        $this->assertContains('deepseek', $providers);
    }

    public function test_a_profile_on_bedrock_is_refused(): void
    {
        $validator = $this->validate(['provider' => 'bedrock']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('provider', $validator->errors()->toArray());
    }

    public function test_a_profile_on_an_sdk_provider_is_accepted(): void
    {
        $this->assertFalse($this->validate(['provider' => 'anthropic'])->fails());
    }

    public function test_a_profile_on_a_provider_named_in_config_is_accepted(): void
    {
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
