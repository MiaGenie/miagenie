<?php

namespace App\Concerns\Requests;

use App\Ai\StepAgentFactory;
use App\Enums\Modality;
use App\Enums\RuleSubType;
use App\Models\ModelProfile;
use Closure;
use Illuminate\Validation\Rule;

/**
 * The modality rules a rule step form shares between create and update.
 *
 * A step's modality is implied by its sub type — the posts image sub types draw, everything else
 * writes — so the form never sends one; it is written on save. An image step has no structured
 * output, its files are the result, so `response_format` is not asked for and the text-only
 * columns are pinned rather than left to whatever the form last held. Its model profile must also
 * name a provider the SDK can generate images with.
 */
trait ValidatesStepModality
{
    /**
     * The sub type the modality follows from: from the input on create, from the stored step on update.
     */
    abstract protected function modalitySubType(): ?RuleSubType;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function modalityRules(): array
    {
        return [
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

    protected function modality(): Modality
    {
        return $this->modalitySubType()?->modality() ?? Modality::TEXT;
    }

    protected function isImage(): bool
    {
        return $this->modality() === Modality::IMAGE;
    }

    /**
     * The columns a modality decides, ready to merge into the step's attributes.
     *
     * @return array{modality: Modality, response_format: mixed, output: mixed}
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
