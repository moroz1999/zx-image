<?php

use ZxImage\Converter;

if (is_file('../vendor/autoload.php')) {
    include_once('../vendor/autoload.php');
}


$converter = new Converter();
$converter->setType('standard')
    ->setPath('example.scr')
    ->setZoom(2)
    ->addPreFilter('atari');

//convert and return image data
if ($binary = $converter->getBinary()) {
    //after conversion we can ask for a mime type of last operation and send it to browser
    if ($imageType = $converter->getResultMime()) {

        header('Content-Type: ' . $imageType);
    }

    //send image contents to browser
    echo $binary;
}
