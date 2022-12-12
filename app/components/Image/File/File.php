<?php

namespace Component\Image\File;

/**
 * A base class that defines the methods available for any file being imported
 */
abstract class File
{
    abstract protected function getError(): string;
    abstract protected function getExtension(): string;
    abstract protected function getName(): string;
    abstract protected function getType(): string;
    abstract protected function getSize(): int;
    abstract protected function getTempName(): string;
    abstract protected function moveTo(string $destination): bool;
}
