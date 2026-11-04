<?php

namespace Component\Image;

use Phalcon\Image\Adapter\{Gd, Imagick};
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\ImageHash\ImageHasher;
use Intervention\ImageHash\Strategies\PerceptualStrategy;


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

    public static function getHasher(string $library = self::LIB_IMAGICK): ImageHasher
    {
        $driver = $library == self::LIB_GD
            ? new GdDriver()
            : new ImagickDriver();

        return new ImageHasher($driver, new PerceptualStrategy());
    }


    /**
     * Generate the perceptual hash for the file - used for finding duplicates
     */
    public static function getPHash(string $filePath, ?ImageHasher $hasher = null): string
    {
        //phpcs:ignore Generic.Files.LineLength
        $Hasher = $hasher ?? self::getHasher();

        return $Hasher->hash($filePath)->toHex();
    }
}
