<?php

namespace Component\Image;

use Component\Image\File\File;

class Name
{
    private float $now;
    private string $hash;

    private string $extension;

    private string $directory;
    public function __construct(File $File)
    {
        $this->now = microtime(true);
        $this->hash = md5($File->getName());
        $this->directory = substr($this->hash, 0, 2);

        $this->extension = $File->getExtension();
        $conversions = \Phalcon\Di\Di::getDefault()->get("config")->image->conversions->toArray();
        if (array_key_exists($this->extension, $conversions)) {
            $this->extension = $conversions[$this->extension];
        }
    }

    public function getName(string $suffix = ""): string
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
}
