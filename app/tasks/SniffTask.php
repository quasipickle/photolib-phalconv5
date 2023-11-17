<?php

/**
 * Wrapper for PHP Codesniffer
 *
 * https://github.com/squizlabs/PHP_CodeSniffer
 */

declare(strict_types=1);

namespace Task;

class SniffTask extends TaskAbstract
{
    public function getActions(): array
    {
        return [
            [
                "Action" => "php (or blank)",
                "Description" => "Sniff PHP code (requires phpcs)"
            ],
            [
                "Action" => "js",
                "Description" => "Sniff Javascript code (requires npm + eslint)"
            ]
        ];
    }

    public function mainAction()
    {
        $this->phpAction();
    }

    public function phpAction()
    {
        $this->Climate->blue("Checking PHP code formatting:");
        $executable_path = $this->config->dirs->file->root . "/vendor/bin/phpcs";

        if (!file_exists($executable_path)) {
            //phpcs:ignore Generic.Files.LineLength
            throw new \Exception("The `phpcs` executable was not found (looked for {$executable_path}).  Make sure you've run `composer install` first.");
        }

        $this->configurePHPCodeSniff($executable_path);
        $Parser = new \Phalcon\Cop\Parser();
        $Parser->parse();
        $showIdentifier = $Parser->getBoolean("s", false) ? " -s" : "";

        $command = $executable_path . $showIdentifier;
        foreach ($this->config->sniff->dirs as $path) {
            $command .= " " . $path;
        }

        passthru($command);
    }

    public function jsAction()
    {
        $this->Climate->blue("Checking Javascript code formatting:");
        passthru("npx eslint --color public/js/**");
        return;
    }

    private function configurePHPCodeSniff($executable_path)
    {
        shell_exec($executable_path . " --config-set default_standard " . $this->config->sniff->standard);
        shell_exec($executable_path . " --config-set colors 1");
        shell_exec($executable_path . " --config-set php_version " . PHP_VERSION_ID);
        if ($this->config->sniff->showProgress) {
            shell_exec($executable_path . " --config-set show_progress 1");
        }
    }
}
