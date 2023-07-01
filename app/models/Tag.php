<?php

namespace Model;

class Tag extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->belongsTo(
            "photo_id",
            Photo::class,
            "id"
        );
    }
}
