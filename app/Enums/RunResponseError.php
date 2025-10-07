<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;

enum RunResponseError: int
{
    use WithTitle;
    use FromName;


    case SERVER_ERROR = 1;
    case RATE_LIMIT_EXCEEDED = 2;
    case INVALID_PROMPT = 3;
    case VECTOR_STORE_TIMEOUT = 4;
    case INVALID_IMAGE = 5;
    case INVALID_IMAGE_FORMAT = 6;
    case INVALID_BASE64_IMAGE = 7;
    case INVALID_IMAGE_URL = 8;
    case IMAGE_TOO_LARGE = 9;
    case IMAGE_TOO_SMALL = 10;
    case IMAGE_PARSE_ERROR = 11;
    case IMAGE_CONTENT_POLICY_VIOLATION = 12;
    case INVALID_IMAGE_MODE = 13;
    case IMAGE_FILE_TOO_LARGE = 14;
    case UNSUPPORTED_IMAGE_MEDIA_TYPE = 15;
    case EMPTY_IMAGE_FILE = 16;
    case FAILED_TO_DOWNLOAD_IMAGE = 17;
    case IMAGE_FILE_NOT_FOUND = 18;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::SERVER_ERROR => 'server_error',
            self::RATE_LIMIT_EXCEEDED => 'rate_limit_exceeded',
            self::INVALID_PROMPT => 'invalid_prompt',
            self::VECTOR_STORE_TIMEOUT => 'vector_store_timeout',
            self::INVALID_IMAGE => 'invalid_image',
            self::INVALID_IMAGE_FORMAT => 'invalid_image_format',
            self::INVALID_BASE64_IMAGE => 'invalid_base64_image',
            self::INVALID_IMAGE_URL => 'invalid_image_url',
            self::IMAGE_TOO_LARGE => 'image_too_large',
            self::IMAGE_TOO_SMALL => 'image_too_small',
            self::IMAGE_PARSE_ERROR => 'image_parse_error',
            self::IMAGE_CONTENT_POLICY_VIOLATION => 'image_content_policy_violation',
            self::INVALID_IMAGE_MODE => 'invalid_image_mode',
            self::IMAGE_FILE_TOO_LARGE => 'image_file_too_large',
            self::UNSUPPORTED_IMAGE_MEDIA_TYPE => 'unsupported_image_media_type',
            self::EMPTY_IMAGE_FILE => 'empty_image_file',
            self::FAILED_TO_DOWNLOAD_IMAGE => 'failed_to_download_image',
            self::IMAGE_FILE_NOT_FOUND => 'image_file_not_found',
        };
    }
}
