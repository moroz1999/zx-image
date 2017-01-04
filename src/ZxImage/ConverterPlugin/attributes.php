<?php
namespace ZxImage;
if (!class_exists('\ZxImage\ConverterPlugin_standard')) {
    include_once('standard.php');
}

class ConverterPlugin_attributes extends ConverterPlugin_standard
{
    protected function loadBits()
    {
        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 768) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            while ($bin = $this->read8BitString()) {
                $attributesArray[] = $bin;
            }
            $resultBits = array('pixelsArray' => $this->generatePixelsArray(), 'attributesArray' => $attributesArray);
            return $resultBits;
        }
        return false;
    }

    protected function generatePixelsArray()
    {
        $pixelsArray = array();
        for ($third = 0; $third < 3; $third++) {
            for ($y = 0; $y < 4; $y++) {
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = '01010101';
                }
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = '10101010';
                }
            }
        }
        return $pixelsArray;
    }

    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);
            if (count($parsedData['attributesData']['flashMap']) > 0) {
                $gifImages = array();

                $image = $this->exportData($parsedData, false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData, true);
                $gifImages[] = $this->getRightPaletteGif($image);

                $delays = array(32, 32);
                $result = $this->buildAnimatedGif($gifImages, $delays);
            } else {
                $image = $this->exportData($parsedData, false);
                $result = $this->makePngFromGd($image);
            }
        }
        return $result;
    }
}
