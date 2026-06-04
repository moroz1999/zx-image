<?php

declare(strict_types=1);

/*
 * Simple example of usage. Put this into some controller of your app.
 */

use ZxImage\Converter;

if (is_file('../vendor/autoload.php')) {
    include_once('../vendor/autoload.php');
}

// Read GET parameters.

$fileParameter = $_GET['file'] ?? null;
$file = is_string($fileParameter) && $fileParameter !== '' ? basename($fileParameter) : null;
if ($file === null || !is_file($file)) {
    $file = 'example.scr';
}

$borderParameter = $_GET['border'] ?? null;
$border = is_string($borderParameter) && is_numeric($borderParameter) ? (int)$borderParameter : null;

$typeParameter = $_GET['type'] ?? null;
$type = is_string($typeParameter) && $typeParameter !== '' ? $typeParameter : 'standard';

$postFilterParameter = $_GET['postfilter'] ?? null;
$postFilter = is_string($postFilterParameter) && $postFilterParameter !== '' ? $postFilterParameter : null;

$preFilterParameter = $_GET['prefilter'] ?? null;
$preFilter = is_string($preFilterParameter) && $preFilterParameter !== '' ? $preFilterParameter : null;

$zoomParameter = $_GET['zoom'] ?? null;
$zoom = is_string($zoomParameter) && is_numeric($zoomParameter) ? (float)$zoomParameter : 1.0;
if ($zoom > 4) {
    $zoom = 1.0;
}

$cacheEnabledParameter = $_GET['cacheEnabled'] ?? null;
$cacheEnabled = is_string($cacheEnabledParameter) && filter_var($cacheEnabledParameter, FILTER_VALIDATE_BOOL);

$converter = new Converter();

//Set image type, path to image in original format and zoom for resulting image
$converter->setType($type)
    ->setPath($file)
    ->setZoom($zoom);

if ($cacheEnabled) {
    $tmpPath = __DIR__ . '/tmp';
    if (!is_dir($tmpPath)) {
        mkdir($tmpPath);
    }
    $converter->setCachePath($tmpPath);
    $converter->setCacheEnabled($cacheEnabled);
}
if ($preFilter !== null) {
    $converter->addPreFilter($preFilter);
}
if ($postFilter !== null) {
    $converter->addPostFilter($postFilter);
}
if ($border !== null) {
    $converter->setBorder($border);
}
//$converter->addPostFilter('MinSize384');
//convert and return image data
$binary = $converter->getBinary();
if ($binary !== null) {
    //after conversion, we can ask for a mime type of last operation and send it to browser
    $imageType = $converter->getResultMime();
    if ($imageType !== null) {
        header('Content-Type: ' . $imageType);
    }
    //send image contents to browser
    echo $binary;
}
