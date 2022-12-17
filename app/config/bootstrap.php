<?php

$Config = require "config.php";
$Container = new Phalcon\Di\FactoryDefault();
$Container->setShared("config", $Config);

require "services/loader.php";
require "services/db.php";
require "services/dispatcher.php";
require "services/router.php";
require "services/view.php";
require "services/url.php";
