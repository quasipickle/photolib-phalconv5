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
    ]
];

return $routes;
