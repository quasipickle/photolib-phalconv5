<?php

namespace Component\Image;

use Component\Image\File\File;

class Path
{
    private float $now;
    private string $hash;
    private string $rootFileDir;

    private string $extension;

    private string $directory;
    public function __construct(File $File, string $rootFileDir)
    {
        $this->rootFileDir = $rootFileDir;
        $this->now = microtime(true);
        $this->hash = md5($File->getName());
        $this->directory = substr($this->hash, 0, 2);

        $this->extension = $File->getExtension();
        $conversions = \Phalcon\Di\Di::getDefault()->get("config")->image->conversions->toArray();
        if (array_key_exists($this->extension, $conversions)) {
            $this->extension = $conversions[$this->extension];
        }
    }

    public function getPath(string $suffix = ""): string
    {
        return sprintf(
            "%s%s%s%s-%s%s.%s",
            DIRECTORY_SEPARATOR,
            $this->directory,
            DIRECTORY_SEPARATOR,
            $this->hash,
            $this->now,
            $suffix,
            $this->extension
        );
    }

    public function getFullPath(string $suffix = ""): string
    {
        return $this->rootFileDir . $this->getPath($suffix);
    }

    public function getFullDir(): string
    {
        return dirname($this->getFullPath());
    }
}
