<?php

namespace ZxImage;

if (!class_exists('\ZxImage\ConverterPlugin_standard')) {
    include_once('standard.php');
}

class ConverterPlugin_multicolor4 extends ConverterPlugin_multicolor
{
    protected $attributeHeight = 4;
    protected $fileSize = 7680;
}
