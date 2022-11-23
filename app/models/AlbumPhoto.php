<?php

namespace Models;

class AlbumPhoto extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->belongsTo(
            "album_id",
            "Album",
            "id"
        );

        $this->hasOne(
            "photo_id",
            "Photo",
            "id"
        );
    }
}
