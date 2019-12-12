<?php

namespace ZxImage;

if (!class_exists('\ZxImage\ConverterPlugin_standard')) {
    include_once('standard.php');
}

class ConverterPlugin_multicolor extends ConverterPlugin_standard
{
    protected $attributeHeight = 2;
    protected $fileSize = 9216;

    protected function loadBits()
    {
        $pixelsArray = [];
        $attributesArray = [];
        if ($this->makeHandle()) {
            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } else {
                    $attributesArray[] = $bin;
                }
                $length++;
            }
            $resultBits = ['pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray];
            return $resultBits;
        }
        return false;
    }
}
