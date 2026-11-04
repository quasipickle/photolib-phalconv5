<?php

namespace Component\Duplicate;

use Model\{Duplicate, Photo};
use Phalcon\Di\Injectable;

class Finder extends Injectable
{
    private int $distance;
    private DuplicateList $ExistingDuplicates;
    private array $newDuplicates;

    public int $duplicatesFound = 0;
    public float $elapsed = 0;

    private ?array $tree = null;

    public function __construct(int $distance)
    {
        $this->distance = $distance;
    }

    public function find()
    {
        $this->ExistingDuplicates = new DuplicateList();
        $this->ExistingDuplicates->addDuplicates(Duplicate::find());
        $this->newDuplicates = [];
        $this->tree = null;
        $start = microtime(true);

        $photos = Photo::find([
            'columns' => 'id, phash'
        ]);

        foreach($photos as $photo) {
            $this->search($photo->id, $photo->phash);
            $this->insert($photo->id, $photo->phash);
        }

        $this->elapsed = microtime(true) - $start;
        $this->duplicatesFound = count($this->newDuplicates);

        foreach($this->newDuplicates as $newDuplicate) { 
            $newDuplicate->save();
        }
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

    private function search(int $id, string $hash): void
    {
        if($this->tree === null)
            return;

        $stack = [$this->tree];

        while(!empty($stack)){
            $current = array_pop($stack);
            $distance = gmp_hamdist('0x' . $hash, '0x' . $current['hash']);
            if($distance <= $this->distance) {
                if(true && !$this->ExistingDuplicates->exists($id, $current['id'])) {
                    $Duplicate = new Duplicate();
                    $Duplicate->primary_id = $id;
                    $Duplicate->secondary_id = $current['id'];
                    $Duplicate->distance = $distance;
                    $this->newDuplicates[] = $Duplicate;
                }
            }

            $minDistance = max(0, $distance - $this->distance);
            $maxDistance = $distance + $this->distance;

            foreach($current['children'] as $edgeDistance => $child) {
                if($edgeDistance >= $minDistance && $edgeDistance <= $maxDistance) {
                    $stack[] = $child;
                } else {
                }
            }
        }
    }
}