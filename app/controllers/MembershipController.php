<?php

namespace Controller;

use Component\Retval;
use Model\{Album, AlbumPhoto, Duplicate, Photo};

class MembershipController extends BaseDeleteFileController
{
    private ?int $albumId;
    private ?int $photoId;

    public function beforeExecuteRoute()
    {
        $this->albumId = $this->request->hasPost("albumId") ? $this->request->getPost("albumId") : null;
        $this->photoId = $this->request->hasPost("photoId") ? $this->request->getPost("photoId") : null;
    }
    public function moveAction()
    {
        return $this->createMembership(true);
    }

    public function addAction()
    {
        return $this->createMembership();
    }

    public function removeAction()
    {
        $Retval = new Retval();

        // See if the photo has other memberships so we're not removing the last one
        $otherMemberships = AlbumPhoto::find([
            "album_id != :albumId: AND photo_id = :photoId:",
            "bind" => [
                "albumId" => $this->albumId,
                "photoId" => $this->photoId
            ]
        ]);
        if (count($otherMemberships) == 0) {
            //phpcs:ignore Generic.Files.LineLength
            return $Retval->message("The photo cannot be removed from this album because this is the only album the photo is in")->response();
        }

        $desiredMembership = AlbumPhoto::findFirst([
            "album_id = :albumId: AND photo_id = :photoId:",
            "bind" => [
                "albumId" => $this->albumId,
                "photoId" => $this->photoId
            ]
        ]);
        if ($desiredMembership == null) {
            //phpcs:ignore Generic.Files.LineLength
            return $Retval->message("The photo cannot be removed from this album because its membership does not exist")->response();
        }

        $desiredMembership->delete();
        return $Retval->success(true)->response();
    }

    public function deleteAction()
    {
        $Retval = new Retval();
        $photo = Photo::findFirst($this->photoId);
        switch ($this->deleteAllPhotoFiles($photo)) {
            case self::ERROR_NO_PHOTO:
                return $Retval->message("The photo could not be deleted because it does not exist.")->response();
            case self::ERROR_DELETE_ORIGINAL:
                //phpcs:ignore Generic.Files.LineLength
                return $Retval->message("Could not delete the original file: " . $photo->path . ", so nothing was deleted.")->response();
            case self::ERROR_DELETE_DISPLAY:
                //phpcs:ignore Generic.Files.LineLength
                return $Retval->message("The original file was deleted, but there was an error deleting the display file: " . $photo->display_path . ". The database record has not been removed.")->response();
            case self::ERROR_DELETE_THUMB:
                //phpcs:ignore Generic.Files.LineLength
                return $Retval->message("The original and display files were deleted, but there was an error deleting the thumb file: " . $photo->thumb_path . ". The database record has not been removed.")->response();
        }

        $duplicates = Duplicate::find([
            "conditions" => "primary_id = :primaryId: OR secondary_id = :secondaryId:",
            "bind" => [
                "primaryId" => $this->photoId,
                "secondaryId" => $this->photoId
            ]
        ]);
        if (count($duplicates)) {
            foreach ($duplicates as $duplicate) {
                $duplicate->delete();
            }
        }

        $photo->delete();
        return $Retval->success(true)->response();
    }

    /**
     * Create a new membership
     *
     * @param boolean $deleteExisting If true, will delete all existing memberships first
     * @return \Phalcon\Http\Response
     */
    private function createMembership($deleteExisting = false): \Phalcon\Http\Response
    {
        $Retval = new Retval();

        $albumId = $this->request->getPost("albumId");
        $photoId = $this->request->getPost("photoId");

        if ($albumId == null) {
            return $Retval->message("The destination album was not specified.")->response();
        }
        if ($photoId == null) {
            return $Retval->message("The target photo was not specified.")->response();
        }

        $Album = Album::findFirst($albumId);
        if ($Album == null || $Album->id != $albumId) {
            return $Retval->message("The destination album does not exist.")->response();
        }

        $this->db->begin();
        if ($deleteExisting) {
            $existingMappings = AlbumPhoto::find(["photo_id = :photoId:", "bind" => [ "photoId" => $photoId]]);
            if (!$existingMappings->delete()) {
                $this->db->rollback();
                //phpcs:ignore Generic.Files.LineLength
                return $Retval->message("There was an error when trying to delete the photo's existing location record.")->response();
            }
        } else {
            $existingMapping = AlbumPhoto::findFirst(["photo_id = :photoId: AND album_id = :albumId:", "bind" => [
                "photoId" => $photoId,
                "albumId" => $albumId
            ]]);
            if ($existingMapping != null) {
                return $Retval->message("That photo is already in that album.")->response();
            }
        }
        $NewMapping = new AlbumPhoto();
        $NewMapping->album_id = $albumId;
        $NewMapping->photo_id = $photoId;
        $NewMapping->position = AlbumPhoto::MAX_POSITION;
        $NewMapping->save();
        $this->db->commit();

        return $Retval->success(true)->response();
    }
}
