<?php

use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Stream;

$Container->setShared('session', function () {
    $Session = new Manager();
    $Stream = new Stream(
        [
            'savePath' => sys_get_temp_dir(),
        ]
    );
    $Session->setAdapter($Stream);
    $Session->start();

    return $Session;
});
