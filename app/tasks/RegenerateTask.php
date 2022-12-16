<?php

/**
 * Task for regenerating images
 */

declare(strict_types=1);

namespace Tasks;

use League\CLImate\CLImate;
use Model\{Album, AlbumPhoto, Photo};
use Component\Image\Image;


class RegenerateTask extends \Phalcon\Cli\Task
{
    private CLImate $Climate;
    public function initialize()
    {
        $this->Climate = new CLIMate();
    }

    public function helpAction(){
        $actions = [
            [
                "Action" => "thumb",
                "Description" => "Regenerate the thumbnail for a single photo.",
                "Params" => "The id of the photo"
            ],
            [
                "Action" => "display",
                "Description" => "Regenerate the display version of a single photo.",
                "Params" => "The id of the photo"
            ],
            [
                "Action" => "thumbs",
                "Description" => "Regenerate the thumbnails of all photos in an album & its descendant albums.",
                "Params" => "The id of the album.  {$this->config->rootAlbumId} will regenerate all thumbnails."
            ],
            [
                "Action" => "displays",
                "Description" => "Regenerate the display versions of all photos in an album & its descendant albums.",
                "Params" => "The id of the album.  {$this->config->rootAlbumId} will regenerate all display versions."
            ],
        ];
        $this->Climate->table($actions);
    }

    public function thumbAction()
    {
        $this->resizeSingle($this->config->image->versions->thumb);
    }

    public function thumbsAction()
    {
        $this->resizeMultiple($this->config->image->versions->thumb);
    }

    public function displayAction()
    {
        $this->resizeSingle($this->config->image->versions->display);
    }

    public function displaysAction()
    {
        $this->resizeMultiple($this->config->image->versions->display);
    }

    private function resizeSingle(\Phalcon\Config\Config $version): void
    {
        $id = $this->getId("Photo");
        $Photo = Photo::findFirst($id);

        if ($Photo == null) {
            $this->Climate->error("Photo #{$id} doesn't exist.");
            exit();
        }
        $this->resize($Photo, $version);
        $this->Climate->green("Done");
    }

    private function resizeMultiple(\Phalcon\Config\Config $version): void
    {
        $id = $this->getId("Album");
        $Album = Album::findFirst($id);
        $this->Climate->bold()->inline("Finding all albums and subalbums of {$Album->name}: ");
        $albumIds = $this->getDescendantAlbumIds($id);
        $this->Climate->inline((string)count($albumIds))->br();

        $this->Climate->bold()->inline("Finding all photos: ");
        $Builder = new \Phalcon\Mvc\Model\Query\Builder();
        $result = $Builder
            ->from([
                "ap" => AlbumPhoto::class,
                "p" => Photo::class
            ])
            ->columns([
                "p.*"
            ])
            ->inWhere("ap.album_id", $albumIds)
            ->andWhere("ap.photo_id = p.id")
            ->getQuery()
            ->execute();
        $this->Climate->inline($result->count())->br();
        $Progress = $this->Climate->progress($result->count());
        foreach($result as $Photo)
        {
            $Progress->advance(0,$Photo->original_filename);
            $this->resize($Photo, $version);
            $Progress->advance(1);
        }
        $this->Climate->green("Done");
    }

    private function getDescendantAlbumIds(int $id): array
    {
        $ids = [$id];

        $albums = Album::find(["album_id = :id:", "bind" => ["id" => $id]]);
        foreach($albums as $Album)
        {
            $ids = array_merge($ids, $this->getDescendantAlbumIds($Album->id));
        }
        return $ids;
    }

    private function resize(Photo $Photo, \Phalcon\Config\Config $version): void
    {
        $srcPath = $this->config->dirs->file->photo . $Photo->path;
        $destinationPath = $this->config->dirs->file->photo . ($version->type == "thumb" ? $Photo->thumb_path : $Photo->display_path);

        $Image = new Image($srcPath);
        $Image->resize($destinationPath, $version->width, $version->height, $version->quality);
    }

    private function getId(string $entityName): int
    {
        $Parser = new \Phalcon\Cop\Parser();
        $params = $Parser->parse();
        if (!array_key_exists(2, $params))
        {
            $this->Climate->error("{$entityName} id must be specified.");
            exit();
        }

        return (int)$params[2];
    }
}