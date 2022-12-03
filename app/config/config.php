<?php

$file_root  = realpath(dirname(__DIR__ . "/../../.."));
$app_file_root   = $file_root . "/app";
$views_file_root = $app_file_root . "/views";

$web_root = substr($file_root, strlen($_SERVER["DOCUMENT_ROOT"]));

$config = [
    "dirs" => [
        "file" => [
            "root"           => $file_root,
            "app"            => $app_file_root,
            "controllers"    => $app_file_root . "/controllers",
            "models"         => $app_file_root . "/models",
            "photo"          => realpath($file_root . "/../photos"),
            "public"         => $file_root . "/public",
            "tasks"          => $app_file_root . "/tasks",
            "views"          => $views_file_root,
            "viewsCompiled" => $views_file_root . "/compiled"
        ],
        "web" => [
            "root" => $web_root,
            "photo" => $web_root. "/../photos"
        ]
    ],
    "rootAlbumId" => 1,
    "sniff" => [
        "dirs" => [
            __DIR__,
            $app_file_root
        ],
        "showProgress" => true,
        "standard"      => "PSR12"
    ],
    "view" => [
        "compileAlways" => true
    ]
];
return new Phalcon\Config\Config($config);