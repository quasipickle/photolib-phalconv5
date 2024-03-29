<?php

namespace Controller;

use Model\{Album, Photo};
use Phalcon\Mvc\Model\Query\Builder as QueryBuilder;

class BaseController extends \Phalcon\Mvc\Controller
{
    protected \Phalcon\Assets\Collection $footerCollection;

    public function initialize(): void
    {
        $this->footerCollection = $this->assets->collection("footer");
    }

    protected function buildBreadcrumbs(Album|null $Album): array
    {
        $breadcrumbs = [];
        while ($Album != null) {
            array_unshift($breadcrumbs, $Album);
            $Album = Album::findFirst(["id = :id:","bind" => ["id" => $Album->album_id]]);
        }

        return $breadcrumbs;
    }

    protected function getSubAlbums(int $id, array $omit = []): array
    {
        $omit[] = 0;// forcing $omit to have at least 1 element.  0 = invalid id so it won't match, but is valid SQL
        $Builder = new QueryBuilder();
        $result = $Builder
            ->from([
                "album" => Album::class
            ])
            ->columns([
                "album.*",
                "photo.*",
                "subAlbumCount" => "COUNT(sub.id)"
            ])
            ->leftJoin(Photo::class, "album.photo_id = photo.id", "photo")
            ->leftJoin(Album::class, "album.id = sub.album_id", "sub")
            ->where("album.album_id = :id:")
            ->andWhere("album.id NOT IN ({omitIds:array})")
            ->orderBy("album.name")
            ->groupBy("album.id")
            ->setBindParams([
                "id" => $id,
                "omitIds" => $omit
            ])
            ->getQuery()
            ->execute();

        $resultArray = [];
        foreach ($result as $row) {
            $Album = $row->album;

            $Album->Featured = $row->photo;
            $Album->subAlbumCount = $row->subAlbumCount ?? 0;
            $resultArray[] = $Album;
        }
        usort($resultArray, fn($a, $b) => strnatcasecmp($a->name, $b->name));

        return $resultArray;
    }
}
