<?php

namespace App\Concerns\Requests;

use App\Enums\ModelTier;
use Illuminate\Validation\Rule;
use Laravel\Ai\Enums\Lab;

trait ValidatesModelProfile
{
    /**
     * The provider must be a key of config/ai.php's `providers`, which is what the SDK resolves a
     * profile's provider against: an openai-compatible instance configured there under its own
     * name is accepted, and an SDK provider that is not configured (Bedrock, TypeSafe) is not.
     *
     * @return array<string, mixed>
     */
    protected function modelProfileRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:255', Rule::in(array_keys(config('ai.providers', [])))],
            'model_tier' => ['required', Rule::enum(ModelTier::class)],
            'model' => ['nullable', 'required_if:model_tier,'.ModelTier::OTHER->value, 'string', 'max:255'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:3600'],
        ];
    }

    /**
     * A model name only belongs to a profile that opted out of the tiers, so it is cleared
     * rather than left behind to confuse the next reader of the row.
     *
     * @return array<string, mixed>
     */
    protected function modelProfileAttributes(): array
    {
        $tier = ModelTier::from($this->input('model_tier'));

        return [
            'name' => $this->input('name'),
            'provider' => $this->input('provider'),
            'model_tier' => $tier,
            'model' => $tier === ModelTier::OTHER ? $this->input('model') : null,
            'timeout' => $this->input('timeout'),
        ];
    }

    /**
     * The providers the SDK ships with that config/ai.php configures, for the admin form's
     * provider select.
     *
     * Bedrock is not configured (it needs aws/aws-sdk-php, which the application does not
     * install), nor is TypeSafe (classification only), so neither is offered.
     *
     * @return array<int, array<string, string>>
     */
    public static function providerOptions(): array
    {
        return collect(Lab::cases())
            ->filter(fn (Lab $lab) => config("ai.providers.{$lab->value}") !== null)
            ->map(fn (Lab $lab) => ['value' => $lab->value, 'title' => $lab->name])
            ->sortBy('title')
            ->values()
            ->all();
    }
}
