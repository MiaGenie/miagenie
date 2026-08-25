<?php

namespace App\Concerns\Requests;

use App\Enums\WorkspaceFileSource;
use App\Enums\WorkspaceFileType;
use App\Support\FileUploader;

trait UploadsContentImages
{
    /**
     * Store the images that came with the answers, keyed by the field they belong to.
     *
     * What is kept in the content is the stored file's name and public url, so a field holds a
     * reference rather than the upload itself. A field that took a single image holds that
     * reference on its own; one that took several holds a list.
     *
     * @return array<string, mixed>
     */
    private function processImages(): array
    {
        $files = [];

        foreach ($this->files as $fields) {
            foreach ($fields as $field => $fieldFiles) {
                $i = 0;
                foreach ($fieldFiles as $file) {
                    $record = FileUploader::createFromBase($file)
                        ->path('workspace/'.$this->route('workspace').'/images')
                        ->disk('public')
                        ->uploadAndInsertWorkspaceFile(
                            WorkspaceFileType::BRIEFING,
                            WorkspaceFileSource::USER
                        );
                    $files[$field][$i]['id'] = $record->name;
                    $files[$field][$i]['path'] = $record->getUrl();
                    $i++;
                }
                $files[$field] = count($fieldFiles) === 1 ? $files[$field][0] : $files[$field];
            }
        }

        return $files;
    }
}
