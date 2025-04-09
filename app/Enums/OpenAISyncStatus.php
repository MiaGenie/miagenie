<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;

enum OpenAISyncStatus: int
{
    use WithTitle;

    case CREATED = 1;
    case UPLOADING = 2;
    case UPLOADED = 3;
    case PENDING = 4;
    case FAILED = 5;
    case TO_DELETE = 6;
    case DELETING = 7;
    case PENDING_DELETION = 8;
    case FAILED_DELETION = 9;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::CREATED => 'created',
            self::UPLOADING => 'uploading',
            self::UPLOADED => 'uploaded',
            self::PENDING => 'pending',
            self::FAILED => 'failed',
            self::TO_DELETE => 'to_delete',
            self::DELETING => 'deleting',
            self::PENDING_DELETION => 'pending_deletion',
            self::FAILED_DELETION => 'failed_deletion'
        };
    }
}
