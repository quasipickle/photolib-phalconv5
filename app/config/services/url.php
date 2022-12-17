<?php

$Container->setShared("url", function () use ($Config) {
    $Url = new Phalcon\Mvc\Url();
    $Url->setBaseUri($Config->dirs->web->root);
    return $Url;
});
