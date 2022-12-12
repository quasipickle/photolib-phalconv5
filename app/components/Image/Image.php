<?php

namespace Component\Image;

use Phalcon\Image\Adapter\{Gd, Imagick};

class Image
{
    public const LIB_GD = "GD";
    public const LIB_IMAGICK = "IMAGICK";
    private string $libToUse;

    private string $srcPath;
    public function __construct(string $srcPath, string|null $lib = self::LIB_IMAGICK)
    {
        $this->libToUse = $lib;
        $this->srcPath = $srcPath;
    }

    public function resize(string $destination, int $width, int $height, int $quality): bool
    {
        $Image = $this->libToUse == self::LIB_GD
            ? new Gd($this->srcPath)
            : new Imagick($this->srcPath);

        $Image->resize($width, $height);
        if ($width < 500 || $height < 500) {
            $Image->sharpen(50);
        }
        $Image->save($destination, 90);
        unset($Image);
        return true;
    }

    /**
     * Generate the perceptual hash for the file - used for finding duplicates
     */
    public static function getPHash(string $filePath, \Jenssegers\ImageHash\ImageHash $Hasher = null): string
    {
        //phpcs:ignore Generic.Files.LineLength
        $Hasher = $Hasher ?? new \Jenssegers\ImageHash\ImageHash(new \Jenssegers\ImageHash\Implementations\DifferenceHash());
        // hasher resizes down to 8x8 anyway, so might as well go with the
        // smaller image to begin with as it's WAAAAY faster and uses MUCH less memory

        // If this line is causing "must return int but returning float"-type errors,
        // see composer.readme
        $hash = (string)$Hasher->hash($filePath)->toInt();
        return $hash;
    }
}
