<?php

namespace Component\Image\Validator;

use Component\Image\File\DownloadedFile;

/**
 * Validates a downloaded file
 */
class DownloadValidator extends \Component\Image\Validator
{
    /**
     * @param \Component\Image\File\DownloadedFile $File The downloaded file
     */
    public function __construct(DownloadedFile $File)
    {
        parent::__construct($File);
    }

    /**
     * Check an uploaded file
     *
     * One of the called methods will throw an exception if there is an error
     */
    public function check()
    {
        $this->checkType();
    }
}
