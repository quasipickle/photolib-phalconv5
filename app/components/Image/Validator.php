<?php

namespace Component\Image;

use Component\Image\File\File;

/**
 * Validates an uploaded file
 */
abstract class Validator
{
    /**
     * The uploaded file
     */
    protected File $File;

    /**
     * @var array<string> The mime types that are allowed
     */
    public $allowedTypes = [];

    /**
     * @param [Class that implements \Component\Image\File\File] $file  The uploaded file
     */
    public function __construct(File $file)
    {
        $this->File = $file;
        $imagickTypes = array_flip(\Imagick::queryFormats());
        $possibleTypes = [
            "JPG" => "image/jpeg",
            "PNG" => "image/png",
            "WEBP" => "image/webp"
        ];
        $this->allowedTypes = array_intersect_key($possibleTypes, $imagickTypes);
    }

    /**
     * Check an file
     */
    abstract public function check();

    /**
     * Check the photo's type
     *
     * @throws \DomainException if type is not allowed
     */
    protected function checkType()
    {
        if (!(in_array($this->File->getType(), $this->allowedTypes))) {
            throw new \DomainException(sprintf("File type %s is not supported", $this->File->getType()));
        }
    }
}
