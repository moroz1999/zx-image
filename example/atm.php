<?php
if (is_file('../vendor/autoload.php')) {
    include_once('../vendor/autoload.php');
}
include_once('../src/ZxImage/Converter.php');

$path = '../atm/';
foreach (new DirectoryIterator($path) as $fileInfo) {
    if ($fileInfo->isDot()) {
        continue;
    }

    $converter = new \ZxImage\Converter();
    $converter->setType('atmega');
    $converter->setPath($fileInfo->getPathname());
    $converter->setSize(2); //2 for 320*240 (256*192 with border)

    //convert and return image data
    if ($binary = $converter->getBinary()) {
        //after conversion we can ask for a mime type of last operation and send it to browser
        $imageType = $converter->getResultMime();
        header('Content-Type: ' . $imageType);

        //send image contents to browser
        file_put_contents($path . $fileInfo->getBasename() . '.png', $binary);
    }

}
