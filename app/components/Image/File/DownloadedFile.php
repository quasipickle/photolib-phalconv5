<?php

namespace Component\Image\File;

/**
 * An implementation of Component\Image\File for downloaded files
 */
class DownloadedFile extends File
{
    private string $tmpPath;
    private string $originalName;

    public function __construct(string $tmpPath, string $originalName)
    {
        $this->tmpPath = $tmpPath;
        $this->originalName = $originalName;
    }

    public function getError(): string
    {
        return "0";
    }

    public function getExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    public function getName(): string
    {
        return $this->originalName;
    }

    public function getType(): string
    {
        return mime_content_type($this->tmpPath);
    }

    public function getSize(): int
    {
        return filesize($this->tmpPath);
    }

    public function getTempName(): string
    {
        return $this->tmpPath;
    }

    public function moveTo(string $destination): bool
    {
        return rename($this->getTempName(), $destination);
    }
}
