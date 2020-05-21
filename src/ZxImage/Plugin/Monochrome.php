<?php

namespace ZxImage\Plugin;


class Monochrome extends Standard
{
    protected $inkColorZX = '000';
    protected $paperColorZX = '111';
    protected $brightnessZX = '1';
    protected $fileSize = 6144;

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
        $pixelsArray = [];
        if ($this->makeHandle()) {
            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                }
                $length++;
            }
            $resultBits = ['pixelsArray' => $pixelsArray];
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = [];
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['attributesData'] = $this->generateAttributesArray();
        return $parsedData;
    }

    protected function generateAttributesArray()
    {
        $inkColorCode = $this->brightnessZX . $this->inkColorZX;
        $paperColorCode = $this->brightnessZX . $this->paperColorZX;
        $attributesData = [];
        for ($y = 0; $y < 24; $y++) {
            for ($x = 0; $x < 32; $x++) {
                $attributesData['inkMap'][$y][$x] = $inkColorCode;
                $attributesData['paperMap'][$y][$x] = $paperColorCode;
            }
        }
        $attributesData['flashMap'] = [];
        return $attributesData;
    }
}
