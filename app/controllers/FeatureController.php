<?php

namespace Controllers;

use Components\Retval;
use Models\Album;

class FeatureController extends BaseController
{
    public function indexAction()
    {
        $Retval = new Retval();

        $albumId = $this->request->getPost("albumId");
        $photoId = $this->request->getPost("photoId");

        if($albumId == null) {
            return $Retval->message("The album for which to feature the photo was not specified.")->response();
        }
        if($photoId == null) {
            return $Retval->message("The photo to be featured was not specified.")->response();
        }

        $Album = Album::findFirst($albumId);
        if($Album == null || $Album->id != $albumId)
        { 
            return $Retval->message("Album does not exist.")->response();
        }

        $Album->photo_id = $photoId;
        $Album->save();

        return $Retval->success(true)->response();
    }
}
