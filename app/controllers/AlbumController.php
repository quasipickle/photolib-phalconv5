<?php

namespace Controllers;

use Helpers\IterableHelper;
use Models\Album;
use Models\AlbumPhoto;
use Models\Photo;

class AlbumController extends BaseController
{
    public function indexAction()
    {
        $albumId = $this->dispatcher->getParam("id");
        $Album = Album::findFirst($albumId);
        if($Album == null)
            exit("Album not found");

        $subAlbums = [];
        $siblingAlbums = [];
        $this->getAlbums($Album, $subAlbums, $siblingAlbums);
        $menuAlbums = count($subAlbums) > 0 ? $subAlbums : $siblingAlbums;

        $this->view->setVars([
            "Album" => $Album,
            "breadcrumbs" => $this->buildBreadcrumbs($Album),
            "Featured" => $Album->Featured,
            "menuAlbums" => $menuAlbums,
            "subAlbums" => $subAlbums,
            "title" => $Album->name,
            "viewingRoot" => $Album->id == $this->config->rootAlbumId
        ]);
    }

    /**
     * Retrieves featured photos & sub album count for all sub albums
     * 
     * This is done to prevent a separate query for every featured photo
     */
    private function getAlbums(Album|null $Album, array &$subAlbums = [], array &$siblingAlbums = []): void
    {
        $subAlbums = $this->getSubAlbums($Album->id);
        if(count($subAlbums) == 0)
            $siblingAlbums = $this->getSubAlbums($Album->album_id);
    }

    private function getSubAlbums(int $id): array
    {
        $Builder = new \Phalcon\Mvc\Model\Query\Builder();
        $result = $Builder
            ->from([
                "album" => Album::class
            ])
            ->columns([
                "album.*",
                "photo.*",
                "subAlbumCount" => "COUNT(sub.id)"
            ])
            ->where("album.album_id = :id:")
            ->leftJoin(Photo::class, "album.photo_id = photo.id", "photo")
            ->leftJoin(Album::class, "album.id = sub.album_id", "sub")
            ->orderBy("album.name")
            ->groupBy("album.id")
            ->setBindParams(["id" => $id])
            ->getQuery()
            ->execute();

        $resultArray = [];
        foreach($result as $row)
        {
            $Album = $row->album;

            $Album->Featured = $row->photo;
            $Album->subAlbumCount = $row->subAlbumCount ?? 0;
            $resultArray[] = $Album;
        }
        return $resultArray;
    }

    private function buildBreadcrumbs(Album|null $Album): array
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
