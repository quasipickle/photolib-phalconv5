<?php

declare(strict_types=1);

namespace Controller;

use Component\Retval;
use Component\Duplicate\Finder;
use Model\{Album, AlbumPhoto, Duplicate, Photo};
use Phalcon\Http\Response;

class DuplicatesController extends BaseDeleteFileController
{
    public function indexAction()
    {
        $this->footerCollection
                ->addJs($this->url->get("/public/js/duplicate.js"), true, false, ["type" => "module"]);

        $ignored = Duplicate::count("ignore = 1");
        $this->view->ignoredDuplicatesExist = $ignored > 0;

        $this->view->distance = $this->session->has("distance")
            ? $this->session->get("distance")
            : $this->config->duplicate->distance;

        $this->view->duplicates = [];
        $duplicates = Duplicate::find(["conditions" => "ignore != 1 OR ignore IS NULL"]);
        if ($duplicates->count() > 0) {
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

            $breadcrumbs = [];
            $discoveredAlbums = [];

            foreach ($duplicates as $Duplicate) {
                $this->getBreadcrumbsForPhoto($Duplicate->primary_id, $breadcrumbs, $discoveredAlbums);
                $this->getBreadcrumbsForPhoto($Duplicate->secondary_id, $breadcrumbs, $discoveredAlbums);
            }

            $this->view->breadcrumbs = $breadcrumbs;
            $this->view->duplicates = $duplicates;
        }
    }

    private function getBreadcrumbsForPhoto(int $photoId, array &$breadcrumbs, array &$discoveredAlbums): void
    {
        $Album = AlbumPhoto::findFirst("photo_id = " . $photoId)->Album;
        if (!array_key_exists($Album->id, $discoveredAlbums)) {
            $discoveredAlbums[$Album->id] = array_filter(
                $this->buildBreadcrumbs($Album),
                fn($album) => $album->id != $this->config->rootAlbumId
            );
        }
        $breadcrumbs[$photoId] = $discoveredAlbums[$Album->id];
    }

    public function takeAction(): Response
    {
        $Retval = new Retval();

        $duplicateId = $this->request->getPost("duplicateId");
        $take        = $this->request->getPost("take");
        $Duplicate   = Duplicate::findFirstById($duplicateId);
        if ($Duplicate == null) {
            return $Retval->success(false)->message("Could not find the duplicate record to process.")->response();
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

        // transfer over feature flag
        $featuringAlbums = Album::findByPhotoId($Delete->id);
        foreach ($featuringAlbums as $Album) {
            $Album->photo_id = $Take->id;
            $Album->save();
        }

        // delete all Duplicate records that concern the Photo being deleted
        $allRelevantDuplicates = Duplicate::find([
            "conditions" => "primary_id = :id: or secondary_id = :id:", 
            "bind" => [
                "id" => $Delete->id
            ]
        ]);
        $deletedDuplicateIds = [];
        foreach ($allRelevantDuplicates as $duplicate) {
            $deletedDuplicateIds[] = $duplicate->id;
            $duplicate->delete();
        }
        $Retval->deletedDuplicates($deletedDuplicateIds);
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

    public function findAction(): Response
    {
        $distance = $this->request->hasPost('distance')
            ? $this->request->getPost('distance', 'int')
            : $this->config->duplicate->distance;
        $Finder = new Finder($distance);
        $Finder->find();
        $this->flash->success("{$Finder->duplicatesFound} duplicate(s).");
        $this->session->set("distance", $distance);
        return $this->response->redirect("/duplicates");
    }

    public function clearAction(): Response
    {
        $this->db->delete("duplicate");
        $this->flash->success("Cleared");
        return $this->response->redirect("/duplicates");
    }
}
