<?php

namespace App\Genie\Images;

use App\Genie\Runs\PromptContext;

/**
 * What one image slot of a step is asked for, as bare placeholders for the step's message.
 *
 * `{{{brief}}}` is the production brief for this slot (an idea's image brief, later), `{{{feedback}}}`
 * a reviewer's correction when the image is being redone, `{{{position}}}` the 1-based slot number.
 * A run type with more to say extends this class and merges its own values — the posts run adds
 * `{{{idea.*}}}` — which is why nothing here is final.
 */
class ImageContext implements PromptContext
{
    public function __construct(
        public readonly ?string $brief = null,
        public readonly ?string $feedback = null,
        public readonly int $position = 1,
    ) {}

    /**
     * @return array<string, string>
     */
    public function values(): array
    {
        return [
            'brief' => $this->brief ?? '',
            'feedback' => $this->feedback ?? '',
            'position' => (string) $this->position,
        ];
    }

    public function hasFeedback(): bool
    {
        return filled($this->feedback);
    }
}
