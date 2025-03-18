<?php

namespace App\Exceptions;

use Exception;

class OpenAIApiKeyMissing extends Exception
{
    public function __construct()
    {
        parent::__construct("Open AI Api Key Missing.");
    }
}
