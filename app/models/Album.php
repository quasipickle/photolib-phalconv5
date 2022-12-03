<?php

namespace Models;

class Album extends \Phalcon\Mvc\Model
{
    /**
     * The number of subalbums for this album.
     * 
     * This is NOT automatically populated - it is manually populated
     * by BaseController::getSubAlbums()
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

        $this->belongsTo(
            "album_id",
            Album::class,
            "id",
            [
                "alias" => "Parent"
            ]
        );
    }

    public function isRootAlbum(): bool
    {
        $rootAlbumId = \Phalcon\Di\Di::getDefault()->get("config")->rootAlbumId;
        return $this->id == $rootAlbumId;
    }

    public function hasSubAlbums(): bool
    {
        return count($this->albums) > 0;
    }

    public function hasPhotos(): bool
    {
        return count($this->photos) > 0;
    }

    public function hasFeatured(): bool
    {
        return $this->Featured != null && $this->Featured->id != null;
    }

    /**
     * Overriding Phalcon\Mvc\Model::jsonSerialize, because that method only
     * serializes database columns, not object properties
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $array = parent::jsonSerialize();
        $array["subAlbumCount"] = $this->subAlbumCount;
        return $array;
    }
}
