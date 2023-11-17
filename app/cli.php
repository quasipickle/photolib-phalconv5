<?php

/**
 * The task runner.  Copied & lightly modified from https://docs.phalcon.io/5.0/en/application-cli
 */

declare(strict_types=1);

use Phalcon\Cli\Console;
use Phalcon\Cli\Dispatcher;
use Phalcon\Cli\Console\Exception as PhalconException;
use Phalcon\Di\FactoryDefault\Cli as CliDI;

// phpcs:disable PSR1.Files.SideEffects
define("DEBUG", false);
define("DEBUG_SQL", false);

require(__DIR__ . "/../vendor/autoload.php");

$Container  = new CliDI();

$Config = require "config/config.php";
$Container->setShared('config', $Config);

require "config/services/loader.php";
require "config/services/db.php";

$Dispatcher = new Dispatcher();
$Dispatcher->setDefaultNamespace('Task');
$Container->setShared('dispatcher', $Dispatcher);

$Console = new Console($Container);

$arguments = [];
foreach ($argv as $k => $arg) {
    if ($k === 1) {
        $arguments['task'] = $arg;
    } elseif ($k === 2) {
        $arguments['action'] = $arg;
    } elseif ($k >= 3) {
        $arguments['params'][] = $arg;
    }
}

try {
    $Console->handle($arguments);
} catch (PhalconException $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
} catch (Exception $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
