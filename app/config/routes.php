<?php

$routes = [
    "/album/:int" => [
        "controller" => "album",
        "action"     => "index",
        "id"         => 1
    ]
];

return $routes;
