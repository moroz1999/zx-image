<?php

use ZxImage\Converter;

if (is_file('../vendor/autoload.php')) {
    include_once('../vendor/autoload.php');
}


$converter = new Converter();
$converter->setType('nxi');
$converter->setPath('example.nxi'); //
$converter->setZoom(1); //2 for 320*240 (256*192 with border)

//convert and return image data
if ($binary = $converter->getBinary()) {
    //after conversion we can ask for a mime type of last operation and send it to browser
    if ($imageType = $converter->getResultMime()) {

        header('Content-Type: ' . $imageType);
    }

    //send image contents to browser
    echo $binary;
}
