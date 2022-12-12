<?php

namespace Model;

class Photo extends \Phalcon\Mvc\Model
{
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
    }

    public function winPercentage(): ?int
    {
        return $this->battles != 0 ? round($this->wins / $this->battles * 100) : null;
    }

    public function firstAlbum(): Album
    {
        return $this->Albums[0];
    }
}
