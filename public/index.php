<?php
require(__DIR__ . "/../vendor/autoload.php");

define("DEBUG", TRUE);
define("DEBUG_SQL", FALSE);

use Tracy\Debugger;
if(DEBUG)
    Debugger::enable();

require "../app/config/bootstrap.php";

$App = new Phalcon\Mvc\Application($Container);
$app_request_url = substr($_SERVER["REQUEST_URI"],strlen($Config->dirs->web->root));
$response = $App->handle($app_request_url);
$response->send();