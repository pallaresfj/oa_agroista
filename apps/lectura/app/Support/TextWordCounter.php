<?php

namespace App\Support;

class TextWordCounter
{
    public static function count(?string $text): int
    {
        $normalized = trim((string) $text);

        if ($normalized === '') {
            return 0;
        }

        preg_match_all("/[\p{L}\p{N}]+(?:['’-][\p{L}\p{N}]+)*/u", $normalized, $matches);

        return count($matches[0] ?? []);
    }
}
