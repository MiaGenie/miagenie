<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;

enum OpenAISyncStatus: int
{
    use WithTitle;

    case UPLOADING = 1;
    case UPLOADED = 2;
    case FAILED_UPLOAD = 3;
    case UPDATING = 4;
    case UPDATED = 5;
    CASE FAILED_UPDATE = 6;
    case DELETING = 7;
    case FAILED_DELETE = 8;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::UPLOADING => 'uploading',
            self::UPLOADED => 'uploaded',
            self::FAILED_UPLOAD => 'failed_upload',
            self::UPDATING => 'updating',
            self::UPDATED => 'updated',
            self::FAILED_UPDATE => 'failed_update',
            self::DELETING => 'deleting',
            self::FAILED_DELETE => 'failed_delete'
        };
    }
}
