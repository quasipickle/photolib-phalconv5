<?php

/**
 * Basically just a help task
 */

declare(strict_types=1);

namespace Task;

use League\CLImate\CLImate;

class MainTask
{
    /**
     * Output all the help
     */
    public function mainAction()
    {
        $tasks = [
            "Duplicates",
            "Regenerate",
            "Sass",
            "Sniff",
        ];
        $reflection = new \ReflectionClass($this);
        $namespace = $reflection->getNamespaceName();

        foreach ($tasks as $task) {
            $taskClassName = sprintf("%s\%sTask", $namespace, $task);
            $taskObject = new $taskClassName();

            $climate = new CLIMate();
            $climate
                ->br()
                ->bold()
                ->green()
                ->inline('"' . strtolower($task) . '" ')->lightGray($taskObject->getDescription());
            $actions = $taskObject->getActions();
            $climate->table($actions);
        }
    }
}
