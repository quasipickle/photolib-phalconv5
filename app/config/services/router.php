<?php

$routes = [
    [
        'pattern' => '/album/:int',
        'name'    => 'album',
        'paths'   => [
            'controller' => 'album',
            'action'     => 'index',
            'id'         => 1,
        ],
    ],

    [
        'pattern' => '/chooser/:int',
        'name'    => 'chooser',
        'paths'   => [
            'controller' => 'chooser',
            'action'     => 'index',
            'id'         => 1,
        ],
    ],

    [
        'pattern' => '/photo/:int',
        'name'    => 'photo',
        'paths'   => [
            'controller' => 'photo',
            'action'     => 'index',
            'id'         => 1,
        ],
    ],

    [
        'pattern' => '/slideshow/:int',
        'name'    => 'slideshow',
        'paths'   => [
            'controller' => 'slideshow',
            'action'     => 'index',
            'id'         => 1,
        ],
    ],
];


$Container->set("router", function () use ($routes) {
    $Router = new Phalcon\Mvc\Router();
    foreach ($routes as $routeConfig) {
        $route = $Router->add($routeConfig['pattern'], $routeConfig['paths']);
        $route->setName($routeConfig['name']);
    }
    return $Router;
});
