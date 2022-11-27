<?php

namespace Models;

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
            "id"
        );
    }

    public function winPercentage() : ?int
    {
        return $this->battles != 0 ? round($this->wins / $this->battles * 100) : null;
    }
}
