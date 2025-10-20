<?php

namespace Model;

class Photo extends \Phalcon\Mvc\Model
{
    public int $id = 0;
    public function initialize()
    {
        $this->hasManyToMany(
            "id",
            AlbumPhoto::class,
            "photo_id",
            "album_id",
            Album::class,
            "id",
            [
                "alias" => "Albums"
            ]
        );

        $this->belongsTo(
            "id",
            Album::class,
            "photo_id"
        );

        $this->hasMany(
            "id",
            Tag::class,
            "photo_id",
            [
                "alias" => "Tags"
            ]
        );
    }

    public function winPercentage(): ?int
    {
        return $this->battles != 0 ? round($this->wins / $this->battles * 100) : null;
    }

    public function area(): int
    {
        return $this->width * $this->height;
    }

    public function firstAlbum(): Album
    {
        return (count($this->Albums)) ? $this->Albums[0] : null;
    }

    /**
     * Redefining with a class-specific return type to kill some errors
     *
     * @param mixed $parameters See Phalcon\Mvc\Model::findFirst
     *
     * @return self
     */
    public static function findFirst(mixed $parameters = null): ?self
    {
        return parent::findFirst($parameters);
    }

    /**
     * This method is used when replacing a photo, to copy all the necessary properties (ie: pretty much all of them)
     *
     * @param Photo $ReplacingPhoto The photo to do the replacing
     *
     * @return void
     */
    public function copyForReplacement(Photo $ReplacingPhoto): void
    {
        $this->date_uploaded = $ReplacingPhoto->date_uploaded;
        $this->path = $ReplacingPhoto->path;
        $this->width = $ReplacingPhoto->width;
        $this->height = $ReplacingPhoto->height;
        $this->thumb_path = $ReplacingPhoto->thumb_path;
        $this->thumb_width = $ReplacingPhoto->thumb_width;
        $this->thumb_height = $ReplacingPhoto->thumb_height;
        $this->display_path = $ReplacingPhoto->display_path;
        $this->display_width = $ReplacingPhoto->display_width;
        $this->display_height = $ReplacingPhoto->display_height;
        $this->mime_type = $ReplacingPhoto->mime_type;
        $this->filesize = $ReplacingPhoto->filesize;
        $this->phash = $ReplacingPhoto->phash;

        $this->battles += $ReplacingPhoto->battles;
        $this->wins += $ReplacingPhoto->wins;
    }
}
