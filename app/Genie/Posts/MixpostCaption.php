<?php

namespace App\Genie\Posts;

/**
 * The two shapes of a post's caption: the plain text the model writes and reads, and the
 * div-per-line HTML Mixpost's editor stores in a version's body.
 */
final class MixpostCaption
{
    /**
     * One `<div>` per line, the way the Mixpost editor serialises a body.
     */
    public static function toHtml(?string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", (string) $text);

        if ($text === '') {
            return '';
        }

        return '<div>'.str_replace("\n", '</div><div>', $text).'</div>';
    }

    /**
     * The plain text of a stored body, one line per block.
     */
    public static function toText(?string $html): string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return '';
        }

        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/(div|p)>\s*<(div|p)[^>]*>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }
}
