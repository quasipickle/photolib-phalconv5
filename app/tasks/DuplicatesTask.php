<?php

/**
 * Task for finding image duplicates
 */

declare(strict_types=1);

namespace Task;

use Helper\DuplicateList;
use Model\{Photo, Duplicate};
use Phalcon\Mvc\Model\Resultset;

class DuplicatesTask extends TaskAbstract
{
    /** @var int The maximum value distance can have - this is due to the DB column being tinyint. */
    public const DISTANCE_MAX = 255;
    private int $distance = 0;

    private DuplicateList $ExistingDuplicates;

    /**
     * Initialize the task
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->Climate->extend("Helper\ProgressPrecision", "ProgressPrecision");
        $this->ExistingDuplicates = new DuplicateList();

        $this->getDistance();
    }

    public function getDescription() : string
    {
        return "Look for photos.  After running, go to /duplicates to process discovered duplicates.";
    }

    /**
     * Get all the actions & descriptions for the help
     */
    public function getActions(): array
    {
        return [
            [
                "Action" => "code (or blank)",
                "Description" => "Use code-based comparison. Much quicker, but requires php-gmp.",
            ],
            [
                "Action" => "db",
                "Description" => "Use database-based comparison.  Much slower, but doesn't require php-gmp.",
            ]
        ];
    }

    public function mainAction()
    {
        if (!function_exists("gmp_hamdist")) {
            $this->Climate->comment("`gmp_hamdist()` does not exist - falling back to the slower, database comparison.");
            $this->Climate->comment("It is suggested you install php-gmp to enable much quicker comparisons.");
            $this->dbAction();
        }
        else {
            $this->codeAction();
        }

    }

    /**
     * Find duplicates using database queries to calculate the distance.
     * 
     * This is slow because a query has to be run for every photo
     */
    public function dbAction()
    {
        $this->ExistingDuplicates->addDuplicates(Duplicate::find());

        $start = microtime(true);
        $found = 0;

        // Get all hashes
        $photos = Photo::find();
        $photos->setHydrateMode(Resultset::HYDRATE_OBJECTS);

        // Prepare the statement for finding Hamming distance
        $query = <<<SQL
            SELECT
                `id`,
                BIT_COUNT(:phash1 ^ `photo`.`phash`) AS distance
            FROM
                `photo`
            WHERE
                BIT_COUNT(:phash2 ^ `photo`.`phash`) <= :distance
        SQL;
        $connection = $this->db;
        $statement = $connection->prepare($query);
        $statement->bindParam(":distance", $this->distance);

        $Progress = $this->Climate->ProgressPrecision(count($photos));
        $Progress->precision(3);

        foreach ($photos as $Photo) {
            $targetId = $Photo->id;
            $statement->bindParam(':phash1', $Photo->phash);
            $statement->bindParam(':phash2', $Photo->phash);
            $statement->execute();
            $results = $statement->fetchAll();

            if (count($results) > 1) {
                foreach ($results as $result) {
                    $duplicateId = $result["id"];
                    $distance = $result["distance"];

                    if ($duplicateId == $Photo->id) {
                        continue;
                    }

                    if ($this->ExistingDuplicates->exists($targetId, $duplicateId)) {
                        continue;
                    }

                    $this->ExistingDuplicates->addPair($targetId, $duplicateId);
                    $found++;

                    $Duplicate = new Duplicate();
                    $Duplicate->primary_id = $targetId;
                    $Duplicate->secondary_id = $duplicateId;
                    $Duplicate->distance = $distance;
                    $Duplicate->save();
                }
            }

            $Progress->advance(1, "Checked: #$targetId; Duplicates found: $found");
        }

        $elapsed = number_format(microtime(true) - $start,2);
        $this->Climate->bold()->inline("Elapsed: ")->out($elapsed . "s");
    }

    /**
     * Find all duplicates using code to calculate the distance.
     * 
     * This is quick because it bails early as soon as a non-duplicate is found
     */
    public function codeAction()
    {
        $this->ExistingDuplicates->addDuplicates(Duplicate::find());
        $start = microtime(true);
        $found = 0;

        // Get all hashes
        $photos = Photo::find(["order"=>"phash"]);
        $photos->setHydrateMode(Resultset::HYDRATE_OBJECTS);
        $lastIndex = $photos->count() - 1;

        $Progress = $this->Climate->ProgressPrecision($photos->count());
        $Progress->precision(3);

        foreach ($photos as $index => $primary) {
            if ($index == $lastIndex) {
                $Progress->current($index+1, "Checked: #$primary->id; Duplicates found: $found");
                continue;
            }

            for ($i = $index + 1; $i < $lastIndex; $i++) {
                $secondary = $photos[$i];

                if ($this->ExistingDuplicates->exists($primary->id, $secondary->id)) {
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

                    $found++;
                }
                else {
                    break;
                }
            }

            $Progress->current($index+1, "Checked: #$primary->id; Duplicates found: $found");
        }
        $elapsed = number_format(microtime(true) - $start, 2);
        $this->Climate->bold()->inline("Elapsed: ")->out($elapsed . "s");
    }

    /**
     * Get the passed ID from the command line
     * @param string $entityName The name of the entity the id should be for.
     * @return int
     */
    private function getDistance(): void
    {
        $Parser = new \Phalcon\Cop\Parser();
        $params = $Parser->parse();

        $desiredDistance = $this->config->duplicate->distance;
        if (array_key_exists(2, $params)) {
            $paramDistance = (int)$params[2];
            if($paramDistance != $desiredDistance){
                $this->Climate->lightBlue("Overriding default distance of $desiredDistance: Using $paramDistance instead.");
                $desiredDistance = $paramDistance;
            }
        }

        if ($desiredDistance > self::DISTANCE_MAX) {
            $this->Climate->warning("The requested distance ($desiredDistance) is too large to store in the database.  Reducing to (self::DISTANCE_MAX)");
            $desiredDistance = self::DISTANCE_MAX;
        }
        
        $this->distance = $desiredDistance;
    }
}
