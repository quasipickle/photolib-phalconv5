<?php
require(__DIR__ . "/../vendor/autoload.php");

define("DEBUG", TRUE);
define("DEBUG_SQL", FALSE);

use Helper\IniHelper;
use Tracy\Debugger;
if(DEBUG)
    Debugger::enable();

// Phalcon 5.4 uses a dynamic property approach that is deprecated in PHP 8.2+
if (str_starts_with(phpversion(), "8.2")) {
    error_reporting(E_ALL & ~E_DEPRECATED);
}

$Config = require "../app/config/bootstrap.php";
$Config->image->maxFileSize = IniHelper::getUploadMaxFilesize();

$App = new Phalcon\Mvc\Application($Container);
$app_request_url = substr($_SERVER["REQUEST_URI"],strlen($Config->dirs->web->root));
$response = $App->handle($app_request_url);
$response->send();