<?php

namespace Controller;

use Component\Retval;
use Model\{Album, AlbumPhoto, Photo, Tag};
use Phalcon\Mvc\Model\Query\Builder as QueryBuilder;

class AlbumController extends BaseController
{
    public function indexAction()
    {
        $albumId = $this->dispatcher->getParam("id");
        $Album = Album::findFirst($albumId);
        if ($Album == null) {
            exit("Album not found");
        }

        $subAlbums = [];
        $siblingAlbums = [];
        $this->getAlbums($Album, $subAlbums, $siblingAlbums);
        $menuAlbums = count($subAlbums) > 0 ? $subAlbums : $siblingAlbums;

        $this->view->setVars(
            [
                "Album" => $Album,
                "breadcrumbs" => $this->buildBreadcrumbs($Album),
                "Featured" => $Album->Featured,
                "menuAlbums" => $menuAlbums,
                "subAlbums" => $subAlbums,
                "title" => $Album->name,
                "tags" => $this->getGroupedTags($albumId),
                "viewingRoot" => $Album->id == $this->config->rootAlbumId
            ]
        );

        $this->footerCollection->addJs(
            "https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js",
            false,
            false,
            ["type" => "module", "defer" => "true"]
        );
    }

    public function createAction()
    {
        $Retval = new Retval();
        $parentAlbumId = $this->request->getPost("parentId");
        $newAlbumName = $this->request->getPost("name");
        if ($newAlbumName == "") {
            return $Retval->message("The new album must have a name.")->response();
        }

        $Parent = Album::findFirst($parentAlbumId);
        if ($Parent == null) {
            return $Retval->message("The specified album in which to create the new album, doesn't exist.")->response();
        }

        $Album = new Album();
        $Album->album_id = $parentAlbumId;
        $Album->name = $newAlbumName;
        $Album->create();

        return $Retval->success(true)->id($Album->id)->response();
    }

    public function renameAction()
    {
        $Retval = new Retval();
        $newName = $this->request->getPost("name");
        if ($newName == "") {
            return $Retval->message("You must provide a new name.")->response();
        }

        $Album = Album::findFirst($this->request->getPost("id"));
        if ($Album == null) {
            return $Retval->message("The album you requested to rename, does not exist.")->response();
        }

        $Album->name = $newName;
        $Album->save();
        return $Retval->success(true)->response();
    }

    public function moveAction()
    {
        $Retval = new Retval();
        $Album = Album::findFirst($this->request->getPost("albumId"));
        $Parent = Album::findFirst($this->request->getPost("parentId"));

        if ($Album == null) {
            return $Retval->message("The album to move was not found.")->response();
        }
        if ($Parent == null) {
            return $Retval->message("The new parent album was not found.")->response();
        }
        if ($Album->id == $Parent->id) {
            return $Retval->message("The album cannot be moved into itself.")->response();
        }

        $Album->album_id = $Parent->id;
        $Album->save();

        return $Retval->success(true)->response();
    }

    public function deleteAction()
    {
        $Retval = new Retval();
        $Album = Album::findFirst($this->request->getPost("albumId"));
        if (count($Album->albums) > 0) {
            return $Retval->message("The album cannot be deleted because it contains sub-albums.")->response();
        }
        if (count($Album->photos) > 0) {
            return $Retval->message("The album cannot be deleted because it cantains photos.")->response();
        }
        if ($Album->id == $this->config->rootAlbumId) {
            return $Retval->message("You cannot delete the root album.")->response();
        }

        $Album->delete();
        return $Retval->success(true)->response();
    }

    public function orderAction()
    {
        $Retval = new Retval();
        $albumId = $this->request->getPost("albumId");
        $order = $this->request->getPost("order", "int");

        $AlbumPhotos = AlbumPhoto::find(["album_id = :id:", "bind" => ["id" => $albumId]]);
        if (count($AlbumPhotos) == 0) {
            return $Retval->message("There are no photos in the passed album: #$albumId")->response();
        }

        $albumPhotosByPhotoId = [];
        foreach ($AlbumPhotos as $AlbumPhoto) {
            $albumPhotosByPhotoId[$AlbumPhoto->photo_id] = $AlbumPhoto;
        }

        foreach ($order as $position => $photoId) {
            if (
                array_key_exists($photoId, $albumPhotosByPhotoId)
                && $albumPhotosByPhotoId[$photoId]->position != $position
            ) {
                $albumPhotosByPhotoId[$photoId]->position = $position;
                $albumPhotosByPhotoId[$photoId]->save();
            }
        };

        return $Retval->success(true)->response();
    }

    /**
     * Retrieves featured photos & sub album count for all sub albums
     *
     * This is done to prevent a separate query for every featured photo
     */
    private function getAlbums(Album|null $Album, array &$subAlbums = [], array &$siblingAlbums = []): void
    {
        $subAlbums = $this->getSubAlbums($Album->id);
        if (count($subAlbums) == 0) {
            $siblingAlbums = $this->getSubAlbums($Album->album_id);
        }
    }

    /**
     * Retrieve an array of tags grouped by photo id
     * @param int $albumId
     * @return array a multi-dimensional array keyed by photo id, valued by an array of Tag models
     */
    private function getGroupedTags(int $albumId): array
    {
        $builder = new QueryBuilder();
        $result = $builder
            ->from([
                "ap" => AlbumPhoto::class,
                "p" => Photo::class,
                "t" => Tag::class
            ])
            ->columns([
                "p.id",
                "t.*"
            ])
            ->andwhere("ap.album_id = :albumId:")
            ->andWhere("ap.photo_id = p.id")
            ->andWhere("t.photo_id = p.id")
            ->orderBy("t.tag")
            ->setBindParams([
                "albumId" => $albumId
            ])
            ->getQuery()
            ->execute();
        $grouped = [];
        foreach ($result as $row) {
            if (!array_key_exists($row->id, $grouped)) {
                $grouped[$row->id] = [];
            }
            $grouped[$row->id][] = $row->t;
        }
        return $grouped;
    }
}
