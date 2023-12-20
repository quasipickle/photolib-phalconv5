<?php

namespace Model;

class Duplicate extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasOne(
            "primary_id",
            Photo::class,
            "id",
            [
                "alias" => "Primary"
            ]
        );
        $this->hasOne(
            "secondary_id",
            Photo::class,
            "id",
            [
                "alias" => "Secondary"
            ]
        );
    }
}
