<?php
namespace ZxImage;
if (!class_exists('\ZxImage\ConverterPlugin_standard')) {
    include_once('standard.php');
}

class ConverterPlugin_timex81 extends ConverterPlugin_standard
{
    protected $attributeHeight = 1;

    //todo: remove duplicate
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

    protected function parseAttributes($attributesArray)
    {
        $x = 0;
        $y = 0;
        $zxY = 0;
        $attributesData = array('inkMap' => array(), 'paperMap' => array(), 'flashMap' => array());
        foreach ($attributesArray as &$bits) {
            $ink = substr($bits, 1, 1) . substr($bits, 5);
            $paper = substr($bits, 1, 4);

            $attributesData['inkMap'][$zxY][$x] = $ink;
            $attributesData['paperMap'][$zxY][$x] = $paper;

            $flashStatus = substr($bits, 0, 1);
            if ($flashStatus == '1') {
                $attributesData['flashMap'][$zxY][$x] = $flashStatus;
            }

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
                $zxY = $this->calculateZXY($y);
            } else {
                $x++;
            }
        }
        return $attributesData;
    }

}