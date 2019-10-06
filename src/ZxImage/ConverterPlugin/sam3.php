<?php

namespace ZxImage;

if (!class_exists('\ZxImage\ConverterPlugin_standard')) {
    include_once('standard.php');
}
if (!class_exists('\ZxImage\SamPlugin')) {
    include_once(__DIR__ . '\..\SamPlugin.php');
}

class ConverterPlugin_sam3 extends ConverterPlugin_standard
{
    use SamPlugin;
    protected $fileSize = 24617;
    protected $width = 512;
    protected $height = 384;
    protected $paletteLength = 4;
    protected $bitPerPixel = 2;
    protected $pixelRatio = 0.5;

    protected function parsePixels($pixelsArray)
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($pixelsArray as &$bits) {
            $p1 = substr($bits, 0, 2);
            $pixelsData[$y][$x] = $p1;
            $x++;
            $p2 = substr($bits, 2, 2);
            $pixelsData[$y][$x] = $p2;
            $x++;
            $p3 = substr($bits, 4, 2);
            $pixelsData[$y][$x] = $p3;
            $x++;
            $p4 = substr($bits, 6, 2);
            $pixelsData[$y][$x] = $p4;
            $x++;

            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }

    protected function exportData($parsedData, $flashedImage = false)
    {
        //workaround for bmp2scr
        $temp = $parsedData['colorsData'][1];
        $parsedData['colorsData'][1] = $parsedData['colorsData'][2];
        $parsedData['colorsData'][2] = $temp;

        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => $pixel) {
                $color = $parsedData['colorsData'][bindec($pixel)];
                imagesetpixel($image, $x, $y * 2, $color);
                imagesetpixel($image, $x, $y * 2 + 1, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }
}
