<?php

namespace App\Genie\Images;

use App\Enums\WorkspaceFileSource;
use App\Enums\WorkspaceFileType;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\AiRunStepFile;
use App\Models\WorkspaceFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\Data\GeneratedImage;
use Throwable;

/**
 * Where a generated image lands: a file on the Genie disk, a WorkspaceFile row, and the slot row
 * that ties it to the run step. The only image class that touches storage or the database.
 */
class ImageStore
{
    /**
     * The file currently filling a slot, or null when the slot is empty.
     */
    public function current(AiRunStep $runStep, int $position): ?AiRunStepFile
    {
        return $runStep->images()
            ->current()
            ->where('position', $position)
            ->latest('id')
            ->first();
    }

    /**
     * Keep a generated image as the current file of a slot.
     *
     * The run is passed rather than read from the step because `AiRun` is workspace scoped and a
     * queued job may not have the scope the admin page has. When the image replaces an earlier
     * one, the earlier row is superseded in the same transaction so a slot never shows two files.
     */
    public function persist(
        AiRunStep $runStep,
        AiRun $run,
        int $position,
        ImagePrompt $prompt,
        GeneratedImage $image,
        ?AiRunStepFile $parent = null,
        ?string $mode = null,
    ): AiRunStepFile {
        $content = $image->content();
        $disk = config('genie.disk');
        $uuid = (string) Str::uuid();
        $extension = $this->extensionFor($image->mime());
        $path = sprintf('workspace/%s/generated/%s.%s', $run->workspace->uuid, $uuid, $extension);

        Storage::disk($disk)->put($path, $content);

        try {
            return DB::transaction(function () use ($runStep, $run, $position, $prompt, $image, $parent, $mode, $content, $disk, $uuid, $extension, $path) {
                $file = WorkspaceFile::create([
                    'uuid' => $uuid,
                    'workspace_id' => $run->workspace_id,
                    'name' => sprintf('step-%d-%s.%s', $position, $uuid, $extension),
                    'mime_type' => $image->mime(),
                    'disk' => $disk,
                    'path' => $path,
                    'size' => strlen($content),
                    'type' => WorkspaceFileType::STEP_IMAGE,
                    'source' => WorkspaceFileSource::GENERATED,
                ]);

                $parent?->update(['superseded_at' => now(), 'revising_at' => null, 'revision_error' => null]);

                $row = AiRunStepFile::create([
                    'run_step_id' => $runStep->id,
                    'workspace_file_id' => $file->id,
                    'position' => $position,
                    'brief' => $prompt->context()->brief,
                    'prompt' => $prompt->text(),
                    'feedback' => $prompt->context()->feedback,
                    'mode' => $mode,
                    'parent_file_id' => $parent?->workspace_file_id,
                ]);

                return $row->setRelation('file', $file);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }

    /**
     * The SDK only reports a MIME type; the extension follows from it, PNG being what OpenAI returns.
     */
    protected function extensionFor(string $mime): string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'png',
        };
    }
}
