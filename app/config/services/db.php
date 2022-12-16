<?php

$Container->setShared("db", function () {
    $connectionParams = require __DIR__ . "/../db.php";
    $conn = new Phalcon\Db\Adapter\Pdo\Mysql($connectionParams);
    return $conn;
});
