<?php

namespace Controllers;

use Helpers\IterableHelper;
use Models\Album;
use Models\Photo;

class AlbumController extends BaseController
{
    public function indexAction()
    {
        $albumId = $this->dispatcher->getParam("id");
        $Album = Album::findFirst($albumId);
        if($Album == null)
            exit("Album not found");

        $this->populateFeatured($Album);
        $menuAlbums = $Album->hasSubAlbums() ? $Album->albums : Album::find([
            "album_id = :id:",
            "bind" => ["id" => $Album->album_id],
            "order" => "name ASC"
        ]);

        $this->view->setVars([
            "Album" => $Album,
            "breadcrumbs" => $this->buildBreadcrumbs($Album),
            "menuAlbums" => $menuAlbums,
            "menuCanGoBack" => $menuAlbums[0]->album_id != $this->config->rootAlbumId,
            "title" => $Album->name,
            "viewingRoot" => $Album->id == $this->config->rootAlbumId
        ]);
    }

    /**
     * Retrieves all the featured photos for an album's sub-albums 
     * in a single query and injects them into the sub-album
     * 
     * This is done to prevent accessing [sub-album].Featured from 
     * triggering a new query every time
     */
    private function populateFeatured(Album|null $Album): void
    {
        if(!$Album->hasSubAlbums())
            return;

        $params = [
            "container" => \Phalcon\Di\Di::getDefault(),
            "models" => [
                "a" => Album::class,
                "p" => Photo::class
            ],
            "columns" => ["[p].*"],
            "conditions" => [
                [
                    "a.photo_id = p.id AND a.album_id = :id:",
                    [
                        "id" => $Album->id
                    ],
                    [
                        "id" => \PDO::PARAM_INT
                    ]
                ]
            ]
        ];

        $builder = new \Phalcon\Mvc\Model\Query\Builder($params);
        $photosResult = $builder->getQuery()->execute();
        $albumsArray = IterableHelper::toArray($Album->albums);

        foreach($photosResult as $photo)
        {
            $filtered = array_filter($albumsArray, function($album) use ($photo){
                if($album->photo_id == $photo->id)
                    return $album;
            });
            foreach($filtered as $albumKey => $album)
            {
                $albumsArray[$albumKey]->Featured = $photo;
            }
        }

        $Album->albums = $albumsArray;
    }

    private function buildBreadcrumbs(Album $Album): array
    {
        $breadcrumbs = [];
        while($Album != null)
        {
            array_unshift($breadcrumbs, $Album);
            $Album = Album::findFirst(["id = :id:","bind" => ["id" => $Album->album_id]]);
        }
        
        return $breadcrumbs;
    }
}
