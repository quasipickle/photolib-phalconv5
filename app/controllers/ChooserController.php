<?php

namespace Controllers;

use Components\Retval;
use Models\Album;

class ChooserController extends BaseController
{
    public function indexAction(int $albumId)
    {
        $omit = $this->request->hasQuery("omit") ? $this->request->getQuery("omit","int") : [];
        
        $Retval = new Retval();
        
        if($albumId == null) {
            return $Retval->message("The album to load was not specified.")->response();
        }

        $Album = Album::findFirst($albumId);
        if($Album == null || $Album->id != $albumId) { 
            return $Retval->message("Album does not exist.")->response();
        }

        $subAlbums = $this->getSubAlbums($albumId, $omit);
        if(count($subAlbums) == 0)
        {
            $Album = $Album->Parent;
            $subAlbums = $this->getSubAlbums($Album->id, $omit);
        }

        return $Retval
            ->success(true)
            ->album($Album)
            ->albums($subAlbums)
            ->parentAlbum($Album->Parent)
            ->response();
    }
}
