<?php

namespace Component\Duplicate;

use Model\Duplicate;

/***
 * Used by Finder
 * Called DuplicateList because "List" is a reserved word
 */
class DuplicateList
{
    private array $list = [];

    public function addPair(int $primaryId, int $secondaryId)
    {
        if (!array_key_exists($primaryId, $this->list)) {
            $this->list[$primaryId] = [];
        }

        if (!isset($this->list[$primaryId][$secondaryId])) {
            $this->list[$primaryId][$secondaryId] = true;
        }
    }

    public function addDuplicate(Duplicate $duplicate)
    {
        $this->addPair($duplicate->primary_id, $duplicate->secondary_id);
    }

    public function addDuplicates(\Phalcon\Mvc\Model\ResultsetInterface $duplicates)
    {
        foreach ($duplicates as $duplicate) {
            $this->addDuplicate($duplicate);
        }
    }

    public function exists(int $primaryId, int $secondaryId): bool
    {
        return isset($this->list[$primaryId][$secondaryId]) || isset($this->list[$secondaryId][$primaryId]);
    }
}
