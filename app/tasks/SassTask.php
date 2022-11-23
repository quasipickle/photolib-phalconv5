<?php

/**
 * Task for Sass compilation
 *
 */

declare(strict_types=1);

namespace Tasks;

class SassTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        $source = realpath($this->config->dirs->file->root) . "/resources/scss/style.scss";
        $target = realpath($this->config->dirs->file->public) . "/css/style.css";
        $Parser = new \Phalcon\Cop\Parser();
        $Parser->parse();
        $style = $Parser->get("s", "compressed");
        $watchCommand = $Parser->getBoolean("w", false) ? "--watch --poll" : "";

        $command = sprintf("sass -s %s %s:%s %s", $style, $source, $target, $watchCommand);
        passthru($command);
    }
}
