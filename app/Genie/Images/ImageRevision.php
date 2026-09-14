<?php

namespace App\Genie\Images;

use App\Enums\ImageRevisionMode;
use App\Models\AiRun;
use App\Models\AiRunStepFile;
use App\Models\RuleStep;
use InvalidArgumentException;
use Throwable;

/**
 * Redoes one generated image.
 *
 * `fix` keeps the composition: the current file goes to the model as a reference and the
 * reviewer's feedback says what to change. `rethink` starts over from a brief, with nothing
 * attached. Either way a rule step's message is the template — the one given, or the step that
 * drew the image — the caller supplies the placeholders (an image context, which a run type may
 * extend), and the new file supersedes the old one in the same slot, on the run step that drew
 * it so the slot's history stays together. Nothing here knows about Mixpost.
 */
class ImageRevision
{
    public function __construct(
        protected ImageGenerator $generator = new ImageGenerator,
        protected ImageStore $store = new ImageStore,
    ) {}

    /**
     * @throws InvalidArgumentException when the mode's required input is missing
     * @throws Throwable when the model call fails; the slot then carries `revision_error`
     */
    public function revise(
        AiRunStepFile $image,
        AiRun $run,
        ImageRevisionMode $mode,
        ImageContext $context,
        string $locale,
        ImageOptions $options = new ImageOptions,
        ?RuleStep $template = null,
    ): AiRunStepFile {
        if ($mode->needsFeedback() && ! $context->hasFeedback()) {
            throw new InvalidArgumentException('Fixing an image needs feedback.');
        }

        if ($mode->needsBrief() && blank($context->brief)) {
            throw new InvalidArgumentException('Rethinking an image needs a brief.');
        }

        $runStep = $image->runStep;

        $this->store->markRevising($image);

        try {
            $prompt = new ImagePrompt(
                $run,
                $template ?? $runStep->step,
                $locale,
                $context,
                $mode->attachesReference() ? $image->file : null,
            );

            $response = $this->generator->generate($prompt, $options);

            return $this->store->persist(
                $runStep,
                $run,
                (int) $image->position,
                $prompt,
                $response->firstImage(),
                $image,
                $mode->value,
            );
        } catch (Throwable $exception) {
            $this->store->markRevisionFailed($image, $exception->getMessage());

            throw $exception;
        }
    }
}
