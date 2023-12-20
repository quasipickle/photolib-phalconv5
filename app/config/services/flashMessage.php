<?php

use Phalcon\Flash\Session as Flash;

$Container->setShared("flash", function () {
    $Flash = new Flash();
    $Flash->setCssClasses([
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning',
    ]);

    return $Flash;
});
