<?php

namespace Component\Duplicate;

use League\CLImate\CLImate;
use Model\{Duplicate, Photo};
use Phalcon\Di\Injectable;

class Finder extends Injectable
{
    private int $distance;
    private DuplicateList $ExistingDuplicates;

    public int $duplicatesFound;
    public int $totalSearched;
    public float $elapsed;
    public ?string $error;
    private ?array $tree = null;

    public function __construct(int $distance)
    {
        $this->distance = $distance;
    }

    public function find(?CLImate $Climate = null): bool
    {
        $this->ExistingDuplicates = new DuplicateList();
        $this->ExistingDuplicates->addDuplicates(Duplicate::find());
        $this->duplicatesFound = 0;
        $this->totalSearched = 0;
        $this->elapsed = 0;
        $this->error = null;
        $this->tree = null;
        $start = microtime(true);

        $rows = Photo::find([
            'columns' => 'id, phash, duplicate_checked'
        ]);

        foreach($rows as $row) {
            $this->insert($row->id, $row->phash);
        }

        $Progress = null;
        if($Climate != null) {
            $Progress = $Climate->Progress(count($rows));
            $Progress->precision(3);
        }

        foreach($rows as $row) {
            if($Climate != null)
                $Progress->advance();
            if($row->duplicate_checked)
                continue;

            $duplicates = $this->search($row->id, $row->phash);
            $this->duplicatesFound += count($duplicates);

            $photo = Photo::findFirst($row->id);
            if($photo == null)
                continue;
           

            $this->db->begin();
            foreach($duplicates as $newDuplicate) { 
                if(!$newDuplicate->save()) {
                    $this->error = 'Error when saving a new duplicate: ' . $newDuplicate->getMessages()[0];
                    $this->db->rollback();
                    return false;
                }
                $this->ExistingDuplicates->addDuplicate($newDuplicate);
            }
            $photo->duplicate_checked = 1;
            if(!$photo->update()) {
                $this->error = 'Error when updating photo record: ' . $photo->getMessages()[0];
                $this->db->rollback();
                return false;
            }
            $this->db->commit();
            $this->totalSearched++;
        }

        $this->elapsed = microtime(true) - $start;

        return true;
    }

    private function insert(int $id, string $hash): void
    {
        $node = [
            'id' => $id,
            'hash' => $hash,
            'children' => []
        ];

        if($this->tree === null) {
            $this->tree = $node;
            return;
        }

        $current = &$this->tree;
        while(true) {
            $distance = gmp_hamdist('0x' . $hash, '0x' . $current['hash']);

            if(!isset($current['children'][$distance])){
                $current['children'][$distance] = $node;
                return;
            }

            $current = &$current['children'][$distance];
        }
    }

    private function search(int $id, string $hash): array
    {
        if($this->tree === null)
            [];

        $duplicates = [];
        $stack = [$this->tree];

        while(!empty($stack)){
            $current = array_pop($stack);
            $distance = 0;
            if($id != $current['id']) {
                $distance = gmp_hamdist('0x' . $hash, '0x' . $current['hash']);
                if($distance <= $this->distance) {
                    if(!$this->ExistingDuplicates->exists($id, $current['id'])) {
                        $Duplicate = new Duplicate();
                        $Duplicate->primary_id = $id;
                        $Duplicate->secondary_id = $current['id'];
                        $Duplicate->distance = $distance;
                        $duplicates[] = $Duplicate;
                    }
                }
            }

            $minDistance = max(0, $distance - $this->distance);
            $maxDistance = $distance + $this->distance;

            foreach($current['children'] as $edgeDistance => $child) {
                if($edgeDistance >= $minDistance && $edgeDistance <= $maxDistance) {
                    $stack[] = $child;
                }
            }
        }
        return $duplicates;
    }

    public function elapsedHuman(): string {
        if ($this->elapsed >= 60) {
            $minutes = $this->elapsed / 60;
            return round($minutes, 2) . 'm';
        } elseif ($this->elapsed > 1) {
            return round($this->elapsed, 2) . 's';
        }
        
        return round($this->elapsed * 1000, 2) . 'ms';
    }
}