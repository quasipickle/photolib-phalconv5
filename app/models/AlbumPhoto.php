<?php

namespace Models;

class AlbumPhoto extends \Phalcon\Mvc\Model
{
    const MAX_POSITION = 999999999;
    
    public function initialize()
    {
        $this->belongsTo(
            "album_id",
            Album::class,
            "id"
        );

        $this->hasOne(
            "photo_id",
            Photo::class,
            "id"
        );
    }
}
