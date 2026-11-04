<?php

/**
 * Task for finding image duplicates
 */

declare(strict_types=1);

namespace Task;

use Component\Duplicate\Finder;
use Model\Photo;

class DuplicatesTask extends TaskAbstract
{
    /** @var int The maximum value distance can have - this is due to the DB column being tinyint. */
    public const DISTANCE_MAX = 255;

    /**
     * Initialize the task
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
    }

    public function getActions(): array
    {
        return [
            [
                "Action" => "blank",
                "Description" => "Run duplicate finder"
            ]
        ];
    }

    public function getDescription(): string
    {
        return "Look for photos.  After running, go to /duplicates to process discovered duplicates.";
    }

    public function mainAction()
    {
        if (!function_exists("gmp_hamdist")) {
            $this->Climate->error("`gmp_hamdist()` does not exist.");
            return;
        }
        $this->find();
    }

    // public function testAction()
    // {
    //     $limit = 30000;
    //     $photos = Photo::find([
    //         'columns' => 'phash',
    //         'order' => 'RAND()',
    //         'limit' => $limit
    //     ])->toArray();
    //     $distances = [];
    //     $min = 64;
    //     $max = 0;
    //     $sum = 0;
    //     for($i = 0; $i < $limit - 2;$i += 2){
    //         $distance = gmp_hamdist('0x' . $photos[$i]['phash'],'0x' . $photos[$i+1]['phash']);
    //         $min = min($min, $distance);
    //         $max = max($max, $distance);
    //         $sum += $distance;
    //         $distances[] = $distance;
    //     }

    //     sort($distances);
    //     $middleIndex = (int)floor($limit / 2 / 2);
    //     $median = $middleIndex % 2 === 0
    //         ? ($distances[$middleIndex - 1] + $distances[$middleIndex + 1]) / 2
    //         : $distances[$middleIndex];
    //     $distribution = array_count_values($distances);


    //     $avg = $sum / $limit;
    //     print_r([
    //         "min" => $min, 
    //         "max" => $max, 
    //         "avg" => $avg, 
    //         "median" => $median, 
    //         "distribution" => $distribution
    //     ]);
    // }

    public function find()
    {
        $distance = $this->getDistance();

        $Finder = new Finder($distance);
        $Finder->find();

        $this->Climate->bold()->inline("Duplicates found: ")->out($Finder->duplicatesFound);
        $this->Climate->bold()->inline("Elapsed: ")->out($Finder->elapsed . "s");
    }


    /**
     * Get the passed ID from the command line
     * @return int
     */
    private function getDistance(): int
    {
        $Parser = new \Phalcon\Cop\Parser();
        $params = $Parser->parse();

        $desiredDistance = $this->config->duplicate->distance;
        if (array_key_exists(2, $params)) {
            $paramDistance = (int)$params[2];
            if ($paramDistance != $desiredDistance) {
                //phpcs:ignore Generic.Files.LineLength
                $this->Climate->lightBlue("Overriding default distance of $desiredDistance: Using $paramDistance instead.");
                $desiredDistance = $paramDistance;
            }
        }

        if ($desiredDistance > self::DISTANCE_MAX) {
            //phpcs:ignore Generic.Files.LineLength
            $this->Climate->warning("The requested distance ($desiredDistance) is too large to store in the database.  Reducing to (self::DISTANCE_MAX)");
            $desiredDistance = self::DISTANCE_MAX;
        }

        return $desiredDistance;
    }
}
