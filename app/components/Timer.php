<?php

/**
 * This class is used to simplify timings
 */

namespace Components;

use Components\Timer\Mark;

class Timer
{
    public static $start;
    public static $marks = [];

    public static function init()
    {
        self::$start = microtime(true);
    }

    public static function mark($key, $description = ""): void
    {
        self::$marks[] = new Mark($key, $description);
    }

    /**
     * Output the timing as a Server-Timing header
     */
    public static function output(): void
    {
        header(sprintf("Server-Timing: '%s'", self::getRender()));
    }

    /**
     * Return the marks in a format expected by the Server Timing API,
     * for viewing in browser Devtools
     *
     * @return string
     */
    private static function getRender(): string
    {
        $rendered = [];
        $running = self::$start;
        // An incrementing letter prefix is necessary because
        // Chrome alphabetizes parameters rather than displaying
        // them in the order they appear in the header
        $prefix = "a";
        foreach (self::$marks as $Mark) {
            $rendered[] = $Mark->getRender($running, $prefix++);
            $running    = $Mark->time;
        }
        $value = implode(', ', $rendered);
        return $value;
    }
}
