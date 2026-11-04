<?php

namespace Component\Image\File;

/**
 * A base class that defines the methods available for any file being imported
 */
abstract class File
{
    abstract public function getError(): string;
    abstract public function getExtension(): string;
    abstract public function getName(): string;
    abstract public function getType(): string;
    abstract public function getSize(): int;
    abstract public function getTempName(): string;
    abstract public function moveTo(string $destination): bool;
}
