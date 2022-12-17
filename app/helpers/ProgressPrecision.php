<?php

namespace Helper;

use League\CLImate\TerminalObject\Dynamic\Progress;

/**
 * Extends CLImate's Progress object, adding precision to the progress percentage
 */
class ProgressPrecision extends Progress
{
    /**
     * The number of decimal points to display
     *
     * @var integer $precision
     */
    protected $precision = 0;

    /**
     * Set the completed percentage precision
     *
     * @param integer $precision The number of decimal places to display
     *
     * @return Progress
     */
    public function precision(int $precision): Progress
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Format the percentage so it looks pretty
     *
     * @param float $percentage The percentage (0-1) to format
     *
     * @return string
     */
    protected function percentageFormatted($percentage)
    {
        $factor = pow(10, $this->precision);
        return round($percentage * 100 * $factor) / $factor . '%';
    }
}
