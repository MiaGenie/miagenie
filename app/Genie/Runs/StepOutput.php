<?php

namespace App\Genie\Runs;

use App\Models\AiRunStep;

/**
 * Stores what a step's structured response produced — strategy fields, ideas, later a post.
 */
interface StepOutput
{
    /**
     * @param  array<string, mixed>  $structured
     */
    public function write(AiRunStep $runStep, array $structured): void;
}
