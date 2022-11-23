<?php

/**
 * Bootstrap all services for the app
 */

$Config = require "config.php";

/**
 * Autoload
 *
 * Composer's autoloader is included in index.php to allow Tracy to be loaded right away
 */
$Loader = new Phalcon\Autoload\Loader();
$Loader->setNamespaces(
    [
        "Controllers" => $Config->dirs->file->controllers,
        "Models"      => $Config->dirs->file->models,
    ]
);
$Loader->register();

$Container = new Phalcon\Di\FactoryDefault();
$Container->setShared("config", $Config);

/**
 * Database
 */
$Container->setShared("db", function () {
    $connectionParams = require "db.php";
    $conn = new Phalcon\Db\Adapter\Pdo\Mysql($connectionParams);
    return $conn;
});

/**
 * Dispatcher
 */
$Container->set("dispatcher", function () {
    $Dispatcher = new Phalcon\Mvc\Dispatcher();
    $Dispatcher->setDefaultNamespace("Controllers");
    return $Dispatcher;
});

/**
 * Routes
 */
$Container->set("router", function () {
    $Router = new Phalcon\Mvc\Router();
    $routes = require "routes.php";
    foreach ($routes as $pattern => $properties) {
        $Router->add($pattern, $properties);
    }
    return $Router;
});

/**
 * View
 */
$Container->setShared("voltService", function (Phalcon\Mvc\View $View) use ($Container, $Config) {
    $Volt = new Phalcon\Mvc\View\Engine\Volt($View, $Container);
    $Volt->setOptions([
        "always" => $Config->view->compile_always,
        "path" => function (string $path) use ($Config): string {
            $relative_path = substr($path, strlen($Config->dirs->file->views));

            $compile_dir = $Config->dirs->file->views_compiled . dirname($relative_path);
            $compile_path = $compile_dir . "/" . basename($path);
            if (!is_dir($compile_dir)) {
                mkdir($compile_dir, 0777, true);
            }
            return $compile_path;
        }
    ]);
    return $Volt;
});
$Container->set("view", function () use ($Config) {
    $View = new Phalcon\Mvc\View();
    $View->setViewsDir($Config->dirs->file->views);
    $View->registerEngines(
        [
            ".phtml" => "voltService"
        ]
    );
    return $View;
});

/**
 * Url
 */
 $Container->set("url", function () use ($Config) {
    $Url = new Phalcon\Mvc\Url();
    $Url->setBaseUri($Config->dirs->web->root);
    return $Url;
 });
