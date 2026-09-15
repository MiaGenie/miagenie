<?php

namespace App\Genie\Images;

use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Models\AiRunStep;
use App\Models\AiRunStepFile;
use App\Models\Log;

/**
 * One genie_logs entry per image call, tied to the run step it was made for.
 *
 * A run step is one rule step attempted in one run, so an image step with several briefs, or a
 * later revision of one of its slots, is several calls on one step; the log is where they are
 * told apart. The only image class besides ImageStore that touches the database.
 */
class ImageCallLog
{
    public function record(
        AiRunStep $runStep,
        ImagePrompt $prompt,
        ?AiRunStepFile $file,
        int $duration,
        ?string $error = null,
        ?string $mode = null,
        ?AiRunStepFile $parent = null,
    ): void {
        Log::create([
            'type' => GenieType::RUN_RESPONSE,
            'action' => GenieSyncAction::CREATE,
            'run_step_id' => $runStep->id,
            'request' => array_merge($prompt->describe(), array_filter([
                'mode' => $mode,
                'parent' => $parent?->file?->uuid,
            ])),
            'response' => $error !== null
                ? ['error' => $error]
                : ['file' => $file?->file?->uuid, 'position' => $file?->position],
            'duration' => $duration,
        ]);
    }
}
