<?php

$Container->setShared("db", function () {
    $connectionParams = require __DIR__ . "/../db.php";
    $conn = new Phalcon\Db\Adapter\Pdo\Mysql($connectionParams);

    if (DEBUG && DEBUG_SQL) {
        $eventsManager = new \Phalcon\Events\Manager();
        $eventsManager->attach(
            "db:beforeQuery",
            function ($event, $connection) {
                bdump($connection->getSQLStatement());
            }
        );
        $conn->setEventsManager($eventsManager);
    }
    return $conn;
});
