<?php

namespace Helpers;

class IterableHelper
{
    /**
     * Convert an iterable (shallow) to an array.
     *
     * @param iterable $toIterate
     * @return array
     */
    public static function toArray(iterable $toIterate): array
    {
        $output = [];
        foreach ($toIterate as $key => $value) {
            $output[$key] = $value;
        }
        return $output;
    }
}