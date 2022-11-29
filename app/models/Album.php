<?php

namespace Models;

class Album extends \Phalcon\Mvc\Model
{
    /**
     * The number of subalbums for this album.
     * 
     * This is NOT automatically populated - it is manually populated
     * by AlbumController::getSubAlbums()
     */
    public int $subAlbumCount = 0;
    public $albums = [];

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

    public function hasFeatured(): bool
    {
        return $this->Featured != null && $this->Featured->id != null;
    }
}
