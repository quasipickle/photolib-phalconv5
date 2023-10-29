<?php

/**
 * Task for regenerating images
 */

declare(strict_types=1);

namespace Tasks;

use Component\Image\Image;
use Helper\ProgressPrecision;
use Model\{Album, AlbumPhoto, Photo};

class RegenerateTask extends TaskAbstract
{
    private const ENTITY_PHOTO = "Photo";
    private const ENTITY_ALBUM = "Album";

    /**
     * Get all the actions & descriptions for the help
     */
    public function getActions(): array
    {
        return [
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
                "Params" => "The id of the album. No parameter will regenerate all thumbs."
            ],
            [
                "Action" => "displays",
                "Description" => "Regenerate the display versions of all photos in an album & its descendant albums.",
                "Params" => "The id of the album.  No parameter will regenerate all display versions."
            ],
        ];
    }

    /**
     * Initialize the task
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->Climate->extend("Helper\ProgressPrecision", "ProgressPrecision");
        if ($this->dispatcher->getActionName()) {
            $iam = trim(`whoami`);
            if ($iam != "root") {
                $this->Climate->backgroundYellow()->black()
                //phpcs:ignore Generic.Files.LineLength
                ->inline("Not running as `root`.  Unless you've ensured permissions you may get fatal file access permisson errors.")
                ->br()->br();
            }
        }
    }

    /**
     * Resize a single thumb
     *
     * @param int id When calling this action, pass a single integer for the photo id
     * @return void
     */
    public function thumbAction()
    {
        $this->resizeSingle($this->config->image->versions->thumb);
    }

    /**
     * Resize multiple thumbs
     *
     * @param int id When calling this action, pass a single integer as the start album id.
     *               All thumbnails within the album and sub albums will be re-generated.
     * @return void
     */
    public function thumbsAction()
    {
        $this->resizeMultiple($this->config->image->versions->thumb);
    }

    /**
     * Resize a single display
     *
     * @param int id When calling this action, pass a single integer for the photo id
     * @return void
     */
    public function displayAction()
    {
        $this->resizeSingle($this->config->image->versions->display);
    }

    /**
     * Resize multiple displays
     *
     * @param int id When calling this action, pass a single integer as the start album id.
     *               All displays within the album and sub albums will be re-generated.
     * @return void
     */
    public function displaysAction()
    {
        $this->resizeMultiple($this->config->image->versions->display);
    }

    /**
     * Get all descendent album ids of the start album
     * @param int $id The id of the start album
     * @return array
     */
    private function getDescendantAlbumIds(int $id): array
    {
        $ids = [$id];

        $albums = Album::find(["album_id = :id:", "bind" => ["id" => $id]]);
        foreach ($albums as $Album) {
            $ids = array_merge($ids, $this->getDescendantAlbumIds($Album->id));
        }
        return $ids;
    }

    /**
     * Resize a single photo
     * @param \Phalcon\Config\Config $version The version definition of the photo to generate
     * @return void
     */
    private function resizeSingle(\Phalcon\Config\Config $version): void
    {
        $id = $this->getId(self::ENTITY_PHOTO);
        $Photo = Photo::findFirst($id);

        if ($Photo == null) {
            $this->Climate->error("Photo #{$id} doesn't exist.");
            exit();
        }
        $this->resize($Photo, $version);
        $this->Climate->green("Done");
    }

    /**
     * Resize multiple photos
     * @param \Phalcon\Config\Config $version The version definition of the photo to generate
     * @return void
     */
    private function resizeMultiple(\Phalcon\Config\Config $version): void
    {
        $id = $this->getId(self::ENTITY_ALBUM);
        $Album = Album::findFirst($id);
        $this->Climate->bold()->inline("Finding all albums and subalbums of {$Album->name}: ");
        $albumIds = $this->getDescendantAlbumIds($id);
        $this->Climate->output((string)count($albumIds));

        $this->Climate->bold()->inline("Finding all photos: ");
        $Builder = new \Phalcon\Mvc\Model\Query\Builder();
        $result = $Builder
            ->from([
                "ap" => AlbumPhoto::class,
                "a" => Album::class,
                "p" => Photo::class
            ])
            ->columns([
                "p.*",
                "a.*"
            ])
            ->inWhere("ap.album_id", $albumIds)
            ->andWhere("ap.photo_id = p.id")
            ->andWhere("ap.album_id = a.id")
            ->orderBy("a.id")
            ->getQuery()
            ->execute();
        $this->Climate->output($result->count());
        $Progress = $this->Climate->ProgressPrecision($result->count());
        $Progress->precision(3);
        foreach ($result as $row) {
            $Progress->advance(
                0,
                sprintf(
                    "%s(#%s): #%s - %s",
                    $row->a->name,
                    $row->a->id,
                    $row->p->id,
                    \Helper\ViewHelper::filesize($row->p->filesize)
                )
            );
            $this->resize($row->p, $version);
            $Progress->advance(1);
        }
        $this->Climate->green("Done");
    }

    /**
     * Resize a photo
     * @param Photo $Photo The Photo model for the photo to regenerate
     * @param \Phalcon\Config\Config $version The version definition of the photo to generate
     * @return void
     */
    private function resize(Photo $Photo, \Phalcon\Config\Config $version): void
    {
        $srcPath = $this->config->dirs->file->photo . $Photo->path;
        $destinationPath = $this->config->dirs->file->photo .
            ($version->type == "thumb" ? $Photo->thumb_path : $Photo->display_path);

        $Image = new Image($srcPath);
        try {
            if (!$Image->resize($destinationPath, $version->width, $version->height, $version->quality)) {
                $this->climate->error("Failed to create image from Photo #" . $Photo->id);
            }
        } catch (\ImagickException $e) {
            if ($e->getCode() == 435) {
                $this->Climate->bold()->red("Unable to open image for processing:")
                    ->tab()->output("Image: {$srcPath}")
                    ->tab()->output("Photo #: {$Photo->id}");
                exit();
            }
        }
    }

    /**
     * Get the passed ID from the command line
     * @param string $entityName The name of the entity the id should be for.
     * @return int
     */
    private function getId(string $entityName): int
    {
        $Parser = new \Phalcon\Cop\Parser();
        $params = $Parser->parse();
        if (!array_key_exists(2, $params)) {
            if ($entityName == self::ENTITY_ALBUM) {
                return $this->config->rootAlbumId;
            }
            $this->Climate->error("{$entityName} id must be specified.");
            exit();
        }

        return (int)$params[2];
    }
}
