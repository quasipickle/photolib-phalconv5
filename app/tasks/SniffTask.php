<?php

/**
 * Wrapper for PHP Codesniffer
 *
 * https://github.com/squizlabs/PHP_CodeSniffer
 */

declare(strict_types=1);

namespace Tasks;

class SniffTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        $executable_path = $this->config->dirs->file->root . "/vendor/bin/phpcs";

        if (!file_exists($executable_path)) {
            //phpcs:ignore Generic.Files.LineLength
            throw new \Exception("The `phpcs` executable was not found (looked for {$executable_path}).  Make sure you've run `composer install` first.");
        }

        $this->configurePHPCodeSniff($executable_path);
        $command = $executable_path;
        foreach ($this->config->sniff->dirs as $path) {
            $command .= " " . $path;
        }

        passthru($command);
    }

    private function configurePHPCodeSniff($executable_path)
    {
        shell_exec($executable_path . " --config-set default_standard " . $this->config->sniff->standard);
        shell_exec($executable_path . " --config-set colors 1");
        shell_exec($executable_path . " --config-set php_version " . PHP_VERSION_ID);
        if ($this->config->sniff->show_progress) {
            shell_exec($executable_path . " --config-set show_progress 1");
        }
    }
}
