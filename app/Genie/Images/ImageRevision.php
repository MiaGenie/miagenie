<?php

namespace App\Genie\Images;

use App\Enums\ImageRevisionMode;
use App\Models\AiRun;
use App\Models\AiRunStep;
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
 * it so the slot's history stays together. The call is one genie_logs entry on that run step.
 * Nothing here knows about Mixpost.
 */
class ImageRevision
{
    public function __construct(
        protected ImageGenerator $generator = new ImageGenerator,
        protected ImageStore $store = new ImageStore,
        protected ImageCallLog $log = new ImageCallLog,
    ) {}

    /**
     * @param  RuleStep|null  $template  the step whose message and profile the call uses; the slot's own step when null
     * @param  AiRunStep|null  $recordOn  the run step the call is logged on; the slot's own step when null
     *
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
        ?AiRunStep $recordOn = null,
    ): AiRunStepFile {
        if ($mode->needsFeedback() && ! $context->hasFeedback()) {
            throw new InvalidArgumentException('Fixing an image needs feedback.');
        }

        if ($mode->needsBrief() && blank($context->brief)) {
            throw new InvalidArgumentException('Rethinking an image needs a brief.');
        }

        $runStep = $image->runStep;
        $recordOn ??= $runStep;

        $this->store->markRevising($image);

        $prompt = new ImagePrompt(
            $run,
            $template ?? $runStep->step,
            $locale,
            $context,
            $mode->attachesReference() ? $image->file : null,
        );

        $started = microtime(true);

        $response = null;

        try {
            $response = $this->generator->generate($prompt, $options);

            $revised = $this->store->persist(
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

            $this->log->record($recordOn, $prompt, null, (int) round(microtime(true) - $started), $exception->getMessage(), $mode->value, $image, $response?->usage);

            throw $exception;
        }

        $this->log->record($recordOn, $prompt, $revised, (int) round(microtime(true) - $started), null, $mode->value, $image, $response->usage);

        return $revised;
    }
}
