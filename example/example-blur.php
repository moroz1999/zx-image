<?php
if (is_file('../vendor/autoload.php')) {
    include_once('../vendor/autoload.php');
}
include_once('../src/ZxImage/Converter.php');

$converter = new \ZxImage\Converter();
$converter->setType('standard')
    ->setPath('example.scr')
    ->setZoom(2)
    ->addPostFilter('blur');

//convert and return image data
if ($binary = $converter->getBinary()) {
    //after conversion we can ask for a mime type of last operation and send it to browser
    $imageType = $converter->getResultMime();
    header('Content-Type: ' . $imageType);

    //send image contents to browser
    echo $binary;
}
