<?php

use Phalcon\Events\{Event, Manager};
use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;

$Container->set("dispatcher", function () {
    $EM = new Manager();
    $EM->attach("dispatch:beforeException", function (Event $Evt, $dispatcher, Exception $Exc) {
        if ($Exc instanceof DispatcherException) {
            $dispatcher->forward([
                "controller" => "index",
                "action" => "notFound"
            ]);
            return false;
        }
    });
    $Dispatcher = new Phalcon\Mvc\Dispatcher();
    $Dispatcher->setDefaultNamespace("Controller");
    $Dispatcher->setEventsManager($EM);
    return $Dispatcher;
});
