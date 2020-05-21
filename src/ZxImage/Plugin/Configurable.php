<?php

namespace ZxImage\Plugin;

interface Configurable
{
    public function __construct($sourceFilePath);

    public function convert();

    public function setBorder($border);

    public function setPalette($palette);

    public function setZoom($zoom);

    public function setRotation($rotation);

    public function setGigascreenMode($mode);
}