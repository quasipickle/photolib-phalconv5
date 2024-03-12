<?php

namespace Model;

class AlbumPhoto extends \Phalcon\Mvc\Model
{
    public const MAX_POSITION = 999999999;

    public function initialize()
    {
        $this->belongsTo(
            "album_id",
            Album::class,
            "id",
            [
                "alias" => "Album"
            ]
        );


        $this->hasOne(
            "photo_id",
            Photo::class,
            "id"
        );
    }
}
