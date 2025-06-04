<?php

namespace App\Concerns;

trait GenieParser

{
    /**
     * @param string $text
     * @param array $replacements
     * @return string
     */
    protected function parseContent(string $text, array $replacements): string
    {
        return preg_replace_callback(
            '/\{\{\{([^}]*)\}\}\}/',
            function (array $keys) use ($replacements) {
                return array_key_exists($keys[1], $replacements) ? $replacements[$keys[1]] : '';
            },
            $text
        );
    }
}
