<?php

namespace Controllers;

use Models\Album;

class AlbumController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $albumId = $this->dispatcher->getParam("id");

        $Album = Album::findFirst($albumId);
        $this->view->Album = $Album;
        if (count($Album->albums) == 0) {
            $this->view->SiblingAlbums = Album::find(["album_id = :id:","bind" => ["id" => $Album->album_id]]);
        }
    }
}
