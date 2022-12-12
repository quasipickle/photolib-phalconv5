<?php

use Phalcon\Image\Adapter\Gd;
use Phalcon\Image\Adapter\Imagick;

$path = "/var/www/html/photos/ca/caf5fd8dd7d7e72f03be0ea5d0a5f8d6-1640151062.0491.JPG";
$Image = (isset($_GET["lib"]) && $_GET["lib"] == "gd") ? new Gd($path) : new Imagick($path);

$Image->resize(250,null, Phalcon\Image\Enum::WIDTH);
header("Content-Type: image/jpeg");
echo $Image->render("jpg",90);