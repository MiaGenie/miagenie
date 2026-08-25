<?php

namespace App\Concerns\Requests;

use App\Enums\ModelTier;
use Illuminate\Validation\Rule;
use Laravel\Ai\Enums\Lab;

trait ValidatesModelProfile
{
    /**
     * @return array<string, mixed>
     */
    protected function modelProfileRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:255'],
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
     * The providers the SDK ships with, for the admin form's provider select.
     *
     * @return array<int, array<string, string>>
     */
    public static function providerOptions(): array
    {
        return collect(Lab::cases())
            ->map(fn (Lab $lab) => ['value' => $lab->value, 'title' => $lab->name])
            ->sortBy('title')
            ->values()
            ->all();
    }
}
