<?php
namespace Controller;

use Component\Retval;
use Model\{Album, AlbumPhoto, Photo};
use Phalcon\Mvc\Model\Query\Builder as QueryBuilder;

class SlideshowController extends BaseController
{
    public function indexAction()
    {
        $albumId = $this->dispatcher->getParam("id", "int", 1);
        // Phalcon's bool filtering interprets every string as true - so we need to do filtering manually
        $children = filter_var($this->request->getQuery('children'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $children = $children ?? true; // fallback default

        $urls = [];

        if(!$children) {
            $urls = $this->getPhotos([$albumId]);
        } else {
            $albumIds = $this->getDescendentAlbumIds($albumId);
            $urls = $this->getPhotos($albumIds);
        }

        $retval = new Retval();
        return $retval
            ->success(true)
            ->urls($urls)
            ->response();
    }

    /**
     * Get all album ids that are descendents of the passed id
     */
    private function getDescendentAlbumIds(int $progenitorId): array
    {
        $newAlbumsAdded = true;
        $foundAlbumIds = [$progenitorId];
        $searchAlbumIds = [$progenitorId];
        while($newAlbumsAdded)
        {
            $albums = Album::find([
                'columns' => ['id'],
                'conditions' => 'album_id in ({ids:array})',
                'bind' => ['ids' => $searchAlbumIds],
                'hydration' => \Phalcon\Mvc\Model\Resultset::HYDRATE_ARRAYS
            ]);
            $ids = array_column($albums->toArray(), 'id');
            if(count($ids)) {
                $newAlbumsAdded = true;
                $foundAlbumIds = array_unique(array_merge($foundAlbumIds, $ids));
                $searchAlbumIds = $ids;
            } else {
                $newAlbumsAdded = false;
            }
        }

        return $foundAlbumIds;
    }

    private function getPhotos(array $albumIds): array
    {
        $builder = new QueryBuilder();
        $result = $builder
            -> from([
                'ap' => AlbumPhoto::class,
                'p' => Photo::class
            ])
            ->columns([
                'p.display_path'
            ])
            ->where('ap.album_id in ({albumIds:array})', [
                'albumIds' => $albumIds
            ])
            ->andWhere('ap.photo_id = p.id')
            ->orderBy('RAND()')
            ->getQuery()
            ->execute();

        $paths = [];
        foreach($result as $Row)
        {
            $paths[] = $this->config->dirs->web->photo . $Row->display_path;
        }

        return $paths;
    }
}