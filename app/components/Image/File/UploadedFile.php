<?php

namespace Component\Image\File;

/**
 * An implementation of Component\Image\File for uploaded files
 */
class UploadedFile extends File
{
    private \Phalcon\Http\Request\File $File;

    public function __construct(\Phalcon\Http\Request\File $File)
    {
        $this->File = $File;
    }

    public function getError(): string
    {
        return $this->File->getError();
    }

    public function getExtension(): string
    {
        return $this->File->getExtension();
    }

    public function getName(): string
    {
        return $this->File->getName();
    }

    public function getType(): string
    {
        return $this->File->getRealType();
    }

    public function getSize(): int
    {
        return $this->File->getSize();
    }

    public function getTempName(): string
    {
        return $this->File->getTempName();
    }

    public function moveTo(string $destination): bool
    {
        return $this->File->moveTo($destination);
    }

    public function isUploadedFile(): bool
    {
        return $this->File->isUploadedFile();
    }
}
