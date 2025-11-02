<?php

namespace App\Concerns;

trait CleanAsterisks
{
    /**
     * @param string $text
     * @return string
     */
    protected function cleanAsterisks(string $text): string
    {
        return preg_replace('/\*{2,}/', '', $text);

    }
}
