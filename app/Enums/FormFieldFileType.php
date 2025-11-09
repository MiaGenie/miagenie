<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;

enum FormFieldFileType: int
{
    use WithTitle;

    case IMAGE = 1;
    case DOCUMENT = 2;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::IMAGE => 'image',
            self::DOCUMENT => 'document'
        };
    }
}
