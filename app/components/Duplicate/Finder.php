<?php

namespace Component\Duplicate;

use Model\{Duplicate, Photo};
use Phalcon\Di\Injectable;

class Finder extends Injectable
{
    private int $distance;
    private DuplicateList $ExistingDuplicates;

    public int $duplicatesFound = 0;
    public float $elapsed = 0;

    public function __construct(int $distance)
    {
        $this->ExistingDuplicates = new DuplicateList();
        $this->distance = $distance;
    }

    public function find()
    {
        $this->ExistingDuplicates->addDuplicates(Duplicate::find());
        $start = microtime(true);
        $this->duplicatesFound = 0;

        // Get all hashes
        // convert from a ResultSet to an array with filter() because for some reason,
        // a ResultSet was skipping every second entry.
        $photos = Photo::find(["order" => "phash"])->filter(fn($e) => $e);
        $lastIndex = count($photos) - 1;

        foreach ($photos as $index => $primary) {
            $looped[] = $index;
            if ($index == $lastIndex) {
                continue;
            }

            for ($i = $index + 1; $i < $lastIndex; $i++) {
                $secondary = $photos[$i];

                if ($this->ExistingDuplicates->exists($primary->id, $secondary->id)) {
                    $exists[$primary->id] = $secondary->id;
                    continue;
                }

                $distance = gmp_hamdist('0x' . $primary->phash, '0x' . $secondary->phash);

                if ($distance <= $this->distance) {
                    $this->ExistingDuplicates->addPair($primary->id, $secondary->id);

                    $Duplicate = new Duplicate();
                    $Duplicate->primary_id = $primary->id;
                    $Duplicate->secondary_id = $secondary->id;
                    $Duplicate->distance = $distance;
                    $Duplicate->save();

                    $this->duplicatesFound++;
                } else {
                    break;
                }
            }
        }

        $this->elapsed = number_format(microtime(true) - $start, 2);
    }
}
