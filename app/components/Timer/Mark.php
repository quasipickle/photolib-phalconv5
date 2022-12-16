<?php

namespace Component\Timer;

/**
 * A simple helper class for storing timing marks.
 *
 * Only used by Component\Timer;
 */

class Mark
{
    public $key;
    public $time;
    public $description;

    public function __construct(string $key, string $description = null)
    {
        $this->key         = strtoupper($key);
        $this->time        = microtime(true);
        $this->description = $description;
    }

    /**
     * Get a string representation of this mark in the format expected by the Server Timing API
     *
     * @param $from float microtimestamp to measure the elapsed time from
     * @return string
     */
    public function getRender(float $from, ?string $prefix = null): string
    {
        $elapsed = round(($this->time - $from) * 1000);// milliseconds = microseconds * 1000;
        $string = sprintf('%s%s;dur=%s', $prefix, $this->key, $elapsed);
        if ($this->description != null) {
            $string .= sprintf(';desc="%s"', $this->description);
        }
        return $string;
    }
}
