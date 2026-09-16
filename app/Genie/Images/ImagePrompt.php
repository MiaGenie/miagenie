<?php

namespace App\Genie\Images;

use App\Genie\Runs\RunPrompt;
use App\Models\AiRun;
use App\Models\RuleStep;
use App\Models\WorkspaceFile;

/**
 * The prompt sent to generate one image of a step.
 *
 * The admin writes the image prompt as the step's message, with the same `{{{briefing.*}}}` and
 * `{{{strategy.*}}}` placeholders every run can use plus the slot's own (ImageContext). Images are
 * not conversation turns: each call is stateless, so a revision resends the template with the
 * feedback and attaches the previous file as a reference.
 */
class ImagePrompt extends RunPrompt
{
    public const FEEDBACK_PLACEHOLDER = '{{{feedback}}}';

    public function __construct(
        AiRun $run,
        RuleStep $step,
        string $locale,
        protected ImageContext $image = new ImageContext,
        protected ?WorkspaceFile $reference = null,
    ) {
        parent::__construct($run, $step, $locale, $image);
    }

    public function context(): ImageContext
    {
        return $this->image;
    }

    /**
     * The file the model should revise, when this is a regeneration of an existing image.
     */
    public function reference(): ?WorkspaceFile
    {
        return $this->reference;
    }

    public function step(): RuleStep
    {
        return $this->step;
    }

    /**
     * A template that never mentions `{{{feedback}}}` still has to carry the reviewer's words when
     * an image is redone, so a standard instruction is appended in that case only.
     */
    public function text(): string
    {
        $text = parent::text();

        if (! $this->image->hasFeedback() || $this->templateMentionsFeedback()) {
            return $text;
        }

        return rtrim($text)."\n\n".__('genie.image_revision_instruction', [
            'feedback' => $this->image->feedback,
        ], $this->locale);
    }

    /**
     * A record of what was sent, for the run log.
     */
    public function describe(): array
    {
        return array_merge(parent::describe(), [
            'modality' => 'image',
            'position' => $this->image->position,
            'brief' => $this->image->brief,
            'feedback' => $this->image->feedback,
            'reference' => $this->reference?->uuid,
        ]);
    }

    /**
     * Images do not continue a conversation, so the reviewer's edits of the previous step are not
     * replayed: reviewed strategy values already reach the template through `{{{strategy.*}}}`.
     */
    protected function reviewMessage(): string
    {
        return '';
    }

    protected function templateMentionsFeedback(): bool
    {
        return str_contains((string) $this->step->getTranslation('message', $this->locale), self::FEEDBACK_PLACEHOLDER);
    }
}
