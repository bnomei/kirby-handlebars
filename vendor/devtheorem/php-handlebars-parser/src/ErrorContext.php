<?php

namespace DevTheorem\HandlebarsParser;

class ErrorContext
{
    public static function getErrorContext(string $before, string $after): string
    {
        // truncate to max of 20 chars before removing line breaks
        $after = substr($after, 0, 20);

        if (strlen($before) > 20) {
            $before = '...' . substr($before, -20);
        }

        $before = str_replace(["\r\n", "\n"], '', $before);
        $after = str_replace(["\r\n", "\n"], '', $after);

        return $before . $after . "\n" . str_repeat('-', strlen($before)) . '^';
    }
}
