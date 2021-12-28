<?php

declare(strict_types=1);

/*
 * Simple example of usage. Put this into some controller of your app.
 */

use ZxImage\Converter;

if (is_file('../vendor/autoload.php')) {
    include_once('../vendor/autoload.php');
}

//read GET parameters

$file = null;
if (!empty($_GET['file'])) {
    $file = (string)$_GET['file'];
    $file = basename($file);
}
if (!$file || !is_file($file)) {
    $file = 'example.scr';
}

$border = null;
if (!empty($_GET['border'])) {
    $border = (int)$_GET['border'];
}

if (!empty($_GET['type'])) {
    $type = (string)$_GET['type'];
} else {
    $type = 'standard';
}

if (!empty($_GET['postfilter'])) {
    $postfilter = (string)$_GET['postfilter'];
}

if (!empty($_GET['prefilter'])) {
    $prefilter = (string)$_GET['prefilter'];
}

if (!empty($_GET['zoom'])) {
    $zoom = (float)$_GET['zoom'];
}
if (!isset($zoom) || $zoom > 4) {
    $zoom = 1.0;
}
if (isset($_GET['cacheEnabled'])) {
    $cacheEnabled = (bool)$_GET['cacheEnabled'];
} else {
    $cacheEnabled = false;
}


$converter = new Converter();

//Set image type, path to image in original format and zoom for resulting image
$converter->setType($type)
    ->setPath($file)
    ->setZoom($zoom);

if ($cacheEnabled) {
    $tmpPath = dirname(__FILE__) . '/tmp';
    if (!is_dir($tmpPath)) {
        mkdir($tmpPath);
    }
    $converter->setCachePath($tmpPath);
    $converter->setCacheEnabled($cacheEnabled);
}
if (!empty($prefilter)) {
    $converter->addPreFilter($prefilter);
}
if (!empty($postfilter)) {
    $converter->addPostFilter($postfilter);
}
if ($border !== null) {
    $converter->setBorder($border);
}
//$converter->addPostFilter('MinSize384');
//convert and return image data
if ($binary = $converter->getBinary()) {
    //after conversion we can ask for a mime type of last operation and send it to browser
    if ($imageType = $converter->getResultMime()) {
        header('Content-Type: ' . $imageType);
    }
    //send image contents to browser
    echo $binary;
}
