<?php

namespace Models;

class Album extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasManyToMany(
            "id",
            "Models\AlbumPhoto",
            "album_id",
            "photo_id",
            "Models\Photo",
            "id",
            [
                "alias" => "photos"
            ]
        );

        $this->hasMany(
            "id",
            "Models\Album",
            "album_id",
            [
                "alias" => "albums"
            ]
        );

        $this->hasOne(
            "photo_id",
            "Models\Photo",
            "id",
            [
                "alias" => "Featured"
            ]
        );
    }
}
