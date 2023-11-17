<?php

/**
 * Base abstract task for all tasks
 */

declare(strict_types=1);

namespace Task;

use League\CLImate\CLImate;

abstract class TaskAbstract extends \Phalcon\Cli\Task
{
    public CLIMate $Climate;

    /**
     * Return an array of actions & their description, for outputing help
     */
    abstract public function getActions(): array;

    public function initialize()
    {
        $this->Climate = new CLIMate();
    }

    final public function helpAction()
    {
        $this->Climate->table($this->getActions());
    }

    public function mainAction()
    {
        $this->helpAction();
    }
}
