<?php
namespace ZxImage;

class ConverterPlugin_mc extends ConverterPlugin_standard
{
    protected $attributeHeight = 1;

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 12288) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } else {
                    $attributesArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array('pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray);
            return $resultBits;
        }
        return false;
    }

    protected function calculateZXY($y)
    {
        $result = $y;
        return $result;
    }
}
