<?php

declare(strict_types=1);

namespace Controller;

use Component\Retval;
use Model\{Album, Duplicate, Photo};
use Phalcon\Http\Response;

class DuplicatesController extends BaseDeleteFileController
{
    public function indexAction()
    {
        $this->view->duplicates = [];
        $duplicates = Duplicate::find(["conditions" => "ignore != 1 OR ignore IS NULL"]);
        if (count($duplicates) > 0) {
            $ids = [];
            foreach ($duplicates as $duplicate) {
                $ids[] = $duplicate->primary_id;
                $ids[] = $duplicate->secondary_id;
            }

            $ids = array_values(array_unique($ids));

            $photos = Photo::find([
                "conditions" => "id in ({ids:array})",
                "bind" => [
                    "ids" => $ids
                ]
            ]);

            $photosIndexed = [];
            foreach ($photos as $Photo) {
                $photosIndexed[$Photo->id] = $Photo;
            }

            foreach ($duplicates as $Duplicate) {
                $Duplicate->Primary = $photosIndexed[$Duplicate->primary_id];
                $Duplicate->Secondary = $photosIndexed[$Duplicate->secondary_id];
            }

            $this->footerCollection
                ->addJs($this->url->get("/public/js/duplicate.js"), true, false, ["type" => "module"]);

            $this->view->duplicates = $duplicates;
        }
    }

    public function takeAction(): Response
    {
        $Retval = new Retval();

        $duplicateId = $this->request->getPost("duplicateId");
        $take        = $this->request->getPost("take");
        $Duplicate   = Duplicate::findFirstById($duplicateId);
        if ($Duplicate == null) {
            return $Retval->error("Could not find the duplicate record to process.")->response();
        }

        $Take   = $take == "primary" ? $Duplicate->Primary : $Duplicate->Secondary;
        $Delete = $take == "primary" ? $Duplicate->Secondary : $Duplicate->Primary;

        $Take->battles += $Delete->battles;
        $Take->wins    += $Delete->wins;

        switch ($this->deleteAllPhotoFiles($Delete)) {
            case self::ERROR_NO_PHOTO:
                return $Retval
                    ->message("The discard photo could not be deleted because it does not exist.")
                    ->response();
            case self::ERROR_DELETE_ORIGINAL:
                return $Retval
                    //phpcs:ignore Generic.Files.LineLength
                    ->message("Could not delete the original discard file: " . $Delete->path . ", so no action was taken")
                    ->response();
            case self::ERROR_DELETE_DISPLAY:
                return $Retval
                    //phpcs:ignore Generic.Files.LineLength
                    ->message("The original discard file was deleted, but there was an error deleting the discard display file: " . $Delete->display_path . ". The database record has not been removed.")
                    ->response();
            case self::ERROR_DELETE_THUMB:
                return $Retval
                    //phpcs:ignore Generic.Files.LineLength
                    ->message("The original and display discard files were deleted, but there was an error deleting the discard thumb file: " . $Delete->thumb_path . ". The database record has not been removed.")
                    ->response();
        }

        $featuringAlbums = Album::findByPhotoId($Delete->id);
        foreach($featuringAlbums as $Album)
        {
            $Album->photo_id = $Take->id;
            $Album->save();
        }

        $Duplicate->delete();
        $Delete->delete();
        $Take->save();

        return $Retval->success(true)->response();
    }

    public function ignoreAction(): Response
    {
        $Retval = new Retval();

        $duplicateId = $this->request->getPost("duplicateId");
        $Duplicate = Duplicate::findFirstById($duplicateId);

        if ($Duplicate == null) {
            return $Retval->success(false)->error("Duplication record not found.")->response();
        }

        $Duplicate->ignore = true;
        $Duplicate->save();

        return $Retval->success(true)->response();
    }
}
