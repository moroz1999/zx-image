<?php

namespace ZxImage\Plugin;

interface Configurable
{
    public function __construct(string $sourceFilePath);

    public function convert(): ?string;

    public function setBorder($border);

    public function setPalette($palette);

    public function setZoom($zoom);

    public function setRotation($rotation);

    public function setGigascreenMode($mode);
}