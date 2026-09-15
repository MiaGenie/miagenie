<?php

namespace App\Genie\Posts;

use App\Models\WorkspaceFile;
use Inovector\Mixpost\Abstracts\Image;
use Inovector\Mixpost\Concerns\UsesMediaPath;
use Inovector\Mixpost\Events\Media\UploadingMediaFile;
use Inovector\Mixpost\MediaConversions\MediaImageResizerConversion;
use Inovector\Mixpost\Models\Media;
use Inovector\Mixpost\Support\File;
use Inovector\Mixpost\Support\MediaUploader;

/**
 * Copies a generated workspace file into the Mixpost media library of the current workspace.
 *
 * The same path Mixpost takes for a downloaded stock photo: an upload built from the bytes, the
 * storage-limit event, the uploader with a thumb conversion. Genie keeps its own file; the media
 * row is what a post version references.
 */
class WorkspaceFileToMedia
{
    use UsesMediaPath;

    public function copy(WorkspaceFile $file): Media
    {
        $upload = File::fromFileData($file->filesystem()->get($file->path), $file->name);

        UploadingMediaFile::dispatch($upload);

        return MediaUploader::fromFile($upload)
            ->path(self::mediaWorkspacePathWithDateSubpath())
            ->conversions([
                MediaImageResizerConversion::name('thumb')->width(Image::MEDIUM_WIDTH)->height(Image::MEDIUM_HEIGHT),
            ])
            ->uploadAndInsert();
    }
}
