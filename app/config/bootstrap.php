<?php

$Container = new Phalcon\Di\FactoryDefault();

$Config = require "config.php";
$Container->setShared("config", $Config);

require "services/loader.php";
require "services/session.php";
require "services/db.php";
require "services/dispatcher.php";
require "services/flashMessage.php";
require "services/router.php";
require "services/view.php";
require "services/url.php";
session_start();

return $Config;
