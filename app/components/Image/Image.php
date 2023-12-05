<?php

namespace Component\Image;

use Phalcon\Image\Adapter\{Gd, Imagick};
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

class Image
{
    public const LIB_GD = "GD";
    public const LIB_IMAGICK = "IMAGICK";
    private string $libToUse;

    private string $srcPath;
    public function __construct(string $srcPath, string | null $lib = self::LIB_IMAGICK)
    {
        $this->libToUse = $lib;
        $this->srcPath = $srcPath;
    }

    public function resize(string $destination, int $width, int $height, int $quality): bool
    {
        $Image = $this->libToUse == self::LIB_GD
            ? new Gd($this->srcPath)
            : new Imagick($this->srcPath);

        if ($Image->getWidth() < $width && $Image->getHeight() < $height) {
            $width = $Image->getWidth();
            $height = $Image->getHeight();
        }

        $Image->resize($width, $height);
        if ($width < 500 || $height < 500) {
            $Image->sharpen(50);
        }
        $Image->save($destination, $quality);
        unset($Image);
        return true;
    }

    /**
     * Generate the perceptual hash for the file - used for finding duplicates
     */
    public static function getPHash(string $filePath, ImageHash $Hasher = null): string
    {
        //phpcs:ignore Generic.Files.LineLength
        $Hasher = $Hasher ?? new ImageHash(new DifferenceHash());

        // If this line is causing "must return int but returning float"-type errors,
        // see composer.readme
        $hash = $Hasher->hash($filePath)->toHex();
        return $hash;
    }
}
