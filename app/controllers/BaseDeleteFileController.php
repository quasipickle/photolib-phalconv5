<?php

namespace Controller;

use Model\Photo;

class BaseDeleteFileController extends BaseController
{
    public const ERROR_NO_ERROR = 0;
    public const ERROR_NO_PHOTO = 1;
    public const ERROR_DELETE_ORIGINAL = 2;
    public const ERROR_DELETE_DISPLAY = 3;
    public const ERROR_DELETE_THUMB = 4;

    /**
     * Delete all files for a photo
     */
    protected function deleteAllPhotoFiles(Photo $photo): int
    {
        if ($photo == null) {
            return self::ERROR_NO_PHOTO;
        }

        $originalPath = $this->config->dirs->file->photo . $photo->path;
        if (!$this->deleteFile($originalPath)) {
            return self::ERROR_DELETE_ORIGINAL;
        }

        $displayPath = $this->config->dirs->file->photo . $photo->display_path;
        if (!$this->deleteFile($displayPath)) {
            return self::ERROR_DELETE_DISPLAY;
        }

        $thumbPath = $this->config->dirs->file->photo . $photo->thumb_path;
        if (!$this->deleteFile($thumbPath)) {
            return self::ERROR_DELETE_THUMB;
        }

        return self::ERROR_NO_ERROR;
    }

    /**
     * Delete a file.  Checks that the file is in an allowed location.
     *
     * @param string $path
     * @return boolean
     */
    protected function deleteFile(string $path): bool
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            return true;
        }

        if ($realPath != $path) {
            return false;
        }

        if (strpos($realPath, realpath($this->config->dirs->file->photo)) !== 0) {
            return false;
        }

        return unlink($path);
    }
}
