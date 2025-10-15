<?php

namespace Component\Image\Validator;

use Component\Image\File\UploadedFile;
use Helper\{IniHelper, ViewHelper};

/**
 * Validates an uploaded file
 */
class UploadValidator extends \Component\Image\Validator
{
    /**
     * @param \Component\Image\File\UploadedFile $File The uploaded file
     */
    public function __construct(UploadedFile $File)
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
        $this->uploadIsOK();
        $this->photoIsUploadedFile();
        $this->checkType();
    }

    /**
     * Check if there was an error when uploading
     *
     * @throws \RuntimeException if there was a problem with the file upload
     * @throws \UnexpectedValueException if the upload status wasn't an expected value
     */
    private function uploadIsOk(): bool
    {
        switch ($this->File->getError()) {
            case \UPLOAD_ERR_OK:
                return true;
            case \UPLOAD_ERR_INI_SIZE:
                $int = IniHelper::getUploadMaxFilesize();
                $hr = ViewHelper::filesize($int);
                throw new \RuntimeException("File is larger that PHP allows: $hr.");
            case \UPLOAD_ERR_FORM_SIZE:
                throw new \RuntimeException("File is larger than the form allowed.");
            case \UPLOAD_ERR_PARTIAL:
                throw new \RuntimeException("File was only partially uploaded.");
            case \UPLOAD_ERR_NO_FILE:
                throw new \RuntimeException("No file was uploaded.");
            case \UPLOAD_ERR_NO_TMP_DIR:
                throw new \RuntimeException("There is no temporary directory into which the file can be uploaded.");
            case \UPLOAD_ERR_CANT_WRITE:
                throw new \RuntimeException("There was a failure when trying to write the file to disk.");
            case \UPLOAD_ERR_EXTENSION:
                throw new \RuntimeException("The file upload was stopped by a PHP extension.");
            default:
                // phpcs:ignore Generic.Files.LineLength
                throw new \UnexpectedValueException("An unknown upload status was encountered: " . $this->File->getError());
        }
    }

    /**
     * Check whether or not the initialized tmp_name points to an actual "uploaded file"
     *
     * @throws \InvalidArgumentException if the file isn't considered an uploaded file
     */
    private function photoIsUploadedFile()
    {
        if (!$this->File->isUploadedFile()) {
            throw new \InvalidArgumentException('File is not considered an "uploaded" file');
        }
    }
}
