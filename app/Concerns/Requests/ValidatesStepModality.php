<?php

namespace App\Concerns\Requests;

use App\Ai\StepAgentFactory;
use App\Enums\Modality;
use App\Models\ModelProfile;
use Closure;
use Illuminate\Validation\Rule;

/**
 * The modality rules a rule step form shares between create and update.
 *
 * An image step has no structured output — its files are the result — so `response_format` is
 * not asked for and the text-only columns are pinned on save rather than left to whatever the
 * form last held. Its model profile must also name a provider the SDK can generate images with.
 */
trait ValidatesStepModality
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function modalityRules(): array
    {
        return [
            'modality' => ['required', Rule::enum(Modality::class)->only([Modality::TEXT, Modality::IMAGE])],
            'response_format' => [Rule::requiredIf(fn () => ! $this->isImage())],
        ];
    }

    protected function imageProviderRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $this->isImage()) {
                return;
            }

            $profile = ModelProfile::find($value);

            if ($profile && ! StepAgentFactory::supportsImages($profile)) {
                $fail(__('genie.step_modality_image_provider'));
            }
        };
    }

    protected function modality(): ?Modality
    {
        $value = $this->input('modality');

        return is_numeric($value) ? Modality::tryFrom((int) $value) : null;
    }

    protected function isImage(): bool
    {
        return $this->modality() === Modality::IMAGE;
    }

    /**
     * The columns a modality decides, ready to merge into the step's attributes.
     *
     * @return array{modality: Modality|null, response_format: mixed, output: mixed}
     */
    protected function modalityAttributes(): array
    {
        return [
            'modality' => $this->modality(),
            'response_format' => $this->isImage() ? 'text' : $this->input('response_format'),
            'output' => $this->isImage() ? null : $this->input('output'),
        ];
    }
}
