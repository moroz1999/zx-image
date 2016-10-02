<?php
namespace ZxImage;

class ConverterPlugin_monochrome extends ConverterPlugin_standard
{
    protected $inkColorZX = '000';
    protected $paperColorZX = '111';
    protected $brightnessZX = '1';

    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);

            $image = $this->exportData($parsedData, false);
            $result = $this->makeGifFromGd($image);
        }
        return $result;
    }

    protected function loadBits()
    {
        $pixelsArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) >= 6144) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array('pixelsArray' => $pixelsArray);
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['attributesData'] = $this->generateAttributesArray();
        return $parsedData;
    }

    protected function generateAttributesArray()
    {
        $inkColorCode = $this->brightnessZX . $this->inkColorZX;
        $paperColorCode = $this->brightnessZX . $this->paperColorZX;
        $attributesData = array();
        for ($y = 0; $y < 24; $y++) {
            for ($x = 0; $x < 32; $x++) {
                $attributesData['inkMap'][$y][$x] = $inkColorCode;
                $attributesData['paperMap'][$y][$x] = $paperColorCode;
            }
        }
        $attributesData['flashMap'] = array();
        return $attributesData;
    }
}
