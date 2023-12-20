<?php

//Composer's autoloader is included in index.php to allow Tracy to be loaded right away

$Loader = new Phalcon\Autoload\Loader();
$Loader->setNamespaces(
    [
        "Controller" => $Config->dirs->file->app . "/controllers",
        "Component"  => $Config->dirs->file->app . "/components",
        "Helper"     => $Config->dirs->file->app . "/helpers",
        "Model"      => $Config->dirs->file->app . "/models",
        "Task"       => $Config->dirs->file->app . "/tasks"
    ]
);
$Loader->register();
