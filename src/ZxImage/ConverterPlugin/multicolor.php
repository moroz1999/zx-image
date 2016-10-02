<?php
namespace ZxImage;

class ConverterPlugin_multicolor extends ConverterPlugin_standard
{
    protected $attributeHeight = 2;
    protected $fileSize = 9216;

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == $this->fileSize) {
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
}
