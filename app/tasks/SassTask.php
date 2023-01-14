<?php

/**
 * Tasks for Sass compilation
 *
 */

declare(strict_types=1);

namespace Tasks;

class SassTask extends \Phalcon\Cli\Task
{
    /**
     * One-time compile
     * @return void
     */
    public function compileAction()
    {
        $this->execute();
    }

    /**
     * Compile then watch for changes
     * @return void
     */
    public function watchAction()
    {
        $this->execute(true);
    }

    /**
     * Execute sass
     *
     * @param bool $watch Whether or not to watch for changes after initially compiling
     * @return void
     */
    private function execute(bool $watch = false)
    {
        $source = realpath($this->config->dirs->file->root) . "/resources/scss/style.scss";
        $target = realpath($this->config->dirs->file->public) . "/css/style.css";
        $Parser = new \Phalcon\Cop\Parser();
        $Parser->parse();
        $style = $Parser->get("s", "compressed");
        $watchCommand = $watch ? "--watch --poll" : "";

        $command = sprintf("sass -s %s %s:%s %s", $style, $source, $target, $watchCommand);
        passthru($command);
    }
}
