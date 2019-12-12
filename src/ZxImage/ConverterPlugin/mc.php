<?php

namespace ZxImage;

if (!class_exists('\ZxImage\ConverterPlugin_standard')) {
    include_once('standard.php');
}

class ConverterPlugin_mc extends ConverterPlugin_standard
{
    protected $attributeHeight = 1;
    protected $fileSize = 12288;

    protected function calculateZXY($y)
    {
        $result = $y;
        return $result;
    }
}
