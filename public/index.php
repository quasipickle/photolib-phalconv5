<?php
require(__DIR__ . "/../vendor/autoload.php");

use Tracy\Debugger;
Debugger::enable();

require "../app/config/bootstrap.php";

Components\Timer::init();

$App = new Phalcon\Mvc\Application($Container);

$app_request_url = substr($_SERVER["REQUEST_URI"],strlen($Config->dirs->web->root));

$response = $App->handle($app_request_url);

Components\Timer::output();

$response->send();