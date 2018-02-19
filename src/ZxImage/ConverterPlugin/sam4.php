<?php
namespace ZxImage;
if (!class_exists('\ZxImage\ConverterPlugin_standard')) {
    include_once('standard.php');
}

class ConverterPlugin_sam4 extends ConverterPlugin_standard
{
    protected $fileSize = 24617;

    protected function loadBits()
    {
        $pixelsArray = array();
        $paletteArray = array();
        if ($this->makeHandle()) {

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 256 * 192 / 2) {
                    $pixelsArray[] = $bin;
                } elseif ($length < 256 * 192 / 2 + 16) {
                    $paletteArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array(
                'pixelsArray' => $pixelsArray,
                'paletteArray' => $paletteArray,
            );
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseSam4Palette($data['paletteArray']);
        return $parsedData;
    }

    protected function parsePixels($pixelsArray)
    {
        $x = 0;
        $y = 0;
        $pixelsData = array();
        foreach ($pixelsArray as &$bits) {
            $p1 = substr($bits, 0, 4);
            $pixelsData[$y][$x] = $p1;
            $x++;
            $p2 = substr($bits, 4, 4);
            $pixelsData[$y][$x] = $p2;
            $x++;

            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;

    }

    protected function parseSam4Palette($paletteArray)
    {
        $m = 36;
        $paletteData = array();
        foreach ($paletteArray as &$clutItem) {
            $bright = (int)substr($clutItem, 4, 1);
            $r = ((int)substr($clutItem, 2, 1) * 4 + (int)substr($clutItem, 6, 1) * 2 + $bright) * $m;
            $g = ((int)substr($clutItem, 1, 1) * 4 + (int)substr($clutItem, 5, 1) * 2 + $bright) * $m;
            $b = ((int)substr($clutItem, 3, 1) * 4 + (int)substr($clutItem, 7, 1) * 2 + $bright) * $m;

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
                $color = $parsedData['colorsData'][bindec($pixel)];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }
}
