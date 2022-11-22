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
            "tasks"          => $app_file_root . "/tasks",
            "views"          => $views_file_root,
            "views_compiled" => $views_file_root . "/compiled"
        ],
        "web" => [
            "root" => $web_root
        ]
    ],
    "sniff" => [
        "dirs" => [
            __DIR__,
            $app_file_root
        ],
        "show_progress" => true,
        "standard"      => "PSR12"
    ],
    "view" => [
        "compile_always" => true
    ]
];

return new Phalcon\Config\Config($config);
