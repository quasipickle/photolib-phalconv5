<?php

/**
 * Task for cleaning up files & records
 */
declare(strict_types=1);

namespace Task;

use Model\{Album, AlbumPhoto, Photo};
use Task\TaskAbstract;

class CleanupTask extends TaskAbstract
{
    private string $rogueAlbumName = "__ROGUE";
    public function getDescription(): string
    {
        return "Clean up files and records that don't match records or files.";
    }

    public function getActions(): array
    {
        return [
            [
                "Action" => "norecord",
                "Description" => "Finds image files that don't have a matching database record.",
                "Params" => "-d: deletes the files.  Otherwise just reports the count."
            ],
            [
                "Action" => "rogue",
                "Description" => "Finds records that aren't in any album.  Moves them to the {$this->rogueAlbumName} album."
            ]
        ];
    }

    public function noRecordAction()
    {
        $files = $this->getFiles();
        $noRecord = [];

        $this->Climate->bold()->inline("Finding photos with no database records: ");

        foreach(array_chunk($files, 500) as $chunk) {
            $photos = Photo::find([
                'conditions' => 'path in ({paths:array})',
                'columns' => 'path',
                'bind' => [
                    'paths' => $chunk
                ]
            ]);
            $paths = array_column($photos->toArray(), 'path');

            $missing = array_diff($chunk, $paths);
            $noRecord = array_merge($noRecord, $missing);
        }
        $noRecordCount = count($noRecord);
        $this->Climate->green("Found {$noRecordCount}");

        $Parser = new \Phalcon\Cop\Parser();
        $params = $Parser->parse();
        if(array_key_exists('d', $params) && $noRecordCount > 0) {
            $baseDir = $this->config->dirs->file->photo;
            $deleted = 0;
            $versionDeleted = 0;
            foreach($noRecord as $toDelete) {
                $info = pathinfo($toDelete);
                foreach($this->config->image->versions as $version) {
                    $versionPath = $baseDir . $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . $version->suffix . '.'. $info['extension'];
                    if(file_exists($versionPath)) {
                        unlink($versionPath);
                        $versionDeleted++;                 
                    }
                }
                unlink($baseDir . $toDelete);
                $deleted++;
            }
            $total = $deleted + $versionDeleted;
            $this->Climate->green("Deleted: {$total} (including {$versionDeleted} version files)");
        }
    }

    private function getFiles(): array
    {
        $baseDir = $this->config->dirs->file->photo;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $baseDir,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        $versionSuffixes = [];
        foreach($this->config->image->versions as $version) {
            $versionSuffixes[] = $version->suffix;
        }

        $files = [];
        foreach($iterator as $file) {
            if(!$file->isFile())
                continue;

            $name = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            if(array_any($versionSuffixes, fn($suffix) => str_ends_with($name, $suffix)))
                continue;

            $files[] = substr($file->getPathname(), strlen($baseDir));
        }

        return $files;
    }

    public function rogueAction()
    {
        $this->Climate->inline("Finding rogue records: ");
        $result = $this->db->fetchAll(
            'SELECT p.id FROM photo p WHERE NOT EXISTS(
            SELECT 1 from album_photo ap WHERE ap.photo_id = p.id
            )'
        );
        $rogueCount = count($result);
        if($rogueCount == 0) {
            $this->Climate->green("Found {$rogueCount}");
            return;
        }

        $this->Climate->out("Found {$rogueCount}");

        $rogueAlbum = Album::findFirst([
            'conditions' => 'name = :name: AND album_id = :parentId:',
            'bind' => [
                'name' => $this->rogueAlbumName,
                'parentId' => 1
            ]
        ]);
        if($rogueAlbum == null) {
            $this->Climate->yellow("{$this->rogueAlbumName} album doesn't exist.  Creating.");
            $rogueAlbum = new Album();
            $rogueAlbum->name = $this->rogueAlbumName;
            $rogueAlbum->album_id = 1;
            $rogueAlbum->create();
        }

        foreach($result as $record) {
            $membership = new AlbumPhoto();
            $membership->photo_id = $record['id'];
            $membership->album_id = $rogueAlbum->id;
            $membership->position = 1;
            $membership->create();
        }

        $this->Climate->out("Added {$rogueCount} photos to the {$this->rogueAlbumName} album.");
    }
}