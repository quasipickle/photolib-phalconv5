<?php

namespace Models;

class Album extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasManyToMany(
            "id",
            AlbumPhoto::class,
            "album_id",
            "photo_id",
            Photo::class,
            "id",
            [
                "params" => [
                    "order" => "position ASC"
                ],
                "alias" => "photos"
            ]
        );

        $this->hasMany(
            "id",
            Album::class,
            "album_id",
            [
                "params" => [
                    "order" => "name ASC"
                ],
                "alias" => "albums"
            ]
        );

        $this->hasOne(
            "photo_id",
            Photo::class,
            "id",
            [
                "alias" => "Featured"
            ]
        );
    }

    public function hasSubAlbums(): bool
    {
        return count($this->albums) > 0;
    }

    public function isFeatured(Photo|null $photo): bool
    {
        return $photo != null && $photo->id == ($this->featured?->id ?? -1);
    }
}
