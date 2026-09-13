<?php

namespace App\Genie\Runs;

/**
 * Extra placeholder values a run type adds to a step's message, on top of the briefing and the
 * strategy every run can read.
 */
interface PromptContext
{
    /**
     * Values keyed by the bare placeholder name: `pillar` answers `{{{pillar}}}`.
     *
     * @return array<string, string>
     */
    public function values(): array;
}
