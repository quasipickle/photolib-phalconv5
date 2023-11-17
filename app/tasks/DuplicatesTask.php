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
    }

    /**
     * Get all the actions & descriptions for the help
     */
    public function getActions(): array
    {
        return [];
    }

    public function mainAction()
    {
        $this->ExistingDuplicates->addDuplicates(Duplicate::find());

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
        $distance = min($this->config->duplicate->distance, self::DISTANCE_MAX);
        $statement->bindParam(":distance", $distance);

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

                    $Duplicate = new Duplicate();
                    $Duplicate->primary_id = $targetId;
                    $Duplicate->secondary_id = $duplicateId;
                    $Duplicate->distance = $distance;
                    $Duplicate->save();
                }
            }

            $Progress->advance(1);
        }
    }
}
