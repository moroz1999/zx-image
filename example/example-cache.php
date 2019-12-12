<?php
if (is_file('../vendor/autoload.php')) {
    include_once('../vendor/autoload.php');
}
include_once('../src/ZxImage/Converter.php');

$converter = new \ZxImage\Converter();
$converter->setType('standard')
    ->setPath('example.scr');

$folderForCache = __DIR__ . '/tmp/';
//ensure that there is a folder for converted images cache
if (!is_dir($folderForCache)) {
    mkdir($folderForCache);
}
//set cache path
$converter->setCachePath($folderForCache)
    ->setCacheEnabled(true);

//convert and return image data
if ($binary = $converter->getBinary()) {
    //after conversion we can ask for a mime type of last operation and send it to browser
    $imageType = $converter->getResultMime();
    header('Content-Type: ' . $imageType);

    //send image contents to browser
    echo $binary;
}
