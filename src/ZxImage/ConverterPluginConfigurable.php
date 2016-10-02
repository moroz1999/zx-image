<?php
namespace ZxImage;

interface ConverterPluginConfigurable
{
    public function __construct($sourceFilePath);

    public function convert();

    public function setBorder($border);

    public function setPalette($palette);

    public function setSize($size);

    public function setRotation($rotation);

    public function setGigascreenMode($mode);
}