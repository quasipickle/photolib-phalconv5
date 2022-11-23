<?php

namespace Models;

class Photo extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasManyToMany(
            "id",
            "AlbumPhoto",
            "photo_id",
            "album_id",
            "Album",
            "id"
        );
    }
}
