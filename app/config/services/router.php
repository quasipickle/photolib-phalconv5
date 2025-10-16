<?php

$routes = [
    "/album/:int" => [
        "controller" => "album",
        "action"     => "index",
        "id"         => 1
    ],
    "/chooser/:int" => [
        "controller" => "chooser",
        "action"     => "index",
        "id"         => 1
    ],
    "/photo/:int" => [
        "controller" => "photo",
        "action"     => "index",
        "id"         => 1
    ],
    "/slideshow/:int" => [
        "controller" => "slideshow",
        "action"     => "index",
        "id"         => 1
    ]
];

$Container->set("router", function () use ($routes) {
    $Router = new Phalcon\Mvc\Router();
    foreach ($routes as $pattern => $properties) {
        $Router->add($pattern, $properties);
    }
    return $Router;
});
