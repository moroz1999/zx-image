<?php
namespace ZxImage;
if (!class_exists('\ZxImage\ConverterPlugin_standard')) {
    include_once('standard.php');
}

class ConverterPlugin_ulaplus extends ConverterPlugin_standard
{
    protected $fileSize = 6976;

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        $paletteArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == $this->fileSize) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } elseif ($length < 6912) {
                    $attributesArray[] = $bin;
                } else {
                    $paletteArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array(
                'pixelsArray' => $pixelsArray,
                'attributesArray' => $attributesArray,
                'paletteArray' => $paletteArray,
            );
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseUlaPlusPalette($data['paletteArray']);
        return $parsedData;
    }

    protected function parseUlaPlusPalette($paletteArray)
    {
        $paletteData = array();
        foreach ($paletteArray as &$ulaColor) {
            $r = bindec(substr($ulaColor, 3, 3)) * 32;
            $g = bindec(substr($ulaColor, 0, 3)) * 32;
            $b = bindec(substr($ulaColor, 6, 2)) * 64;

            $redChannel = round(
                ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
            );
            $greenChannel = round(
                ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
            );
            $blueChannel = round(
                ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
            );

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;

            $paletteData[] = $RGB;
        }
        return $paletteData;
    }

    protected function exportData($parsedData, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                $mapPositionX = (int)($x / $this->attributeWidth);
                $mapPositionY = (int)($y / $this->attributeHeight);

                if ($pixel === '1') {
                    $ZXcolor = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                } else {
                    $ZXcolor = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                }

                $color = $parsedData['colorsData'][$ZXcolor];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function parseAttributes($attributesArray)
    {
        $x = 0;
        $y = 0;
        $attributesData = array('inkMap' => array(), 'paperMap' => array());
        foreach ($attributesArray as &$bits) {
            $ink = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 5, 3));
            $paper = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 2, 3)) + 8;

            $attributesData['inkMap'][$y][$x] = $ink;
            $attributesData['paperMap'][$y][$x] = $paper;

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }
}
