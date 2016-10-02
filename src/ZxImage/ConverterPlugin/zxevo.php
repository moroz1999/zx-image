<?php
namespace ZxImage;

class ConverterPlugin_zxevo extends ConverterPlugin
{
    protected $width = 320;
    protected $height = 200;

    public function convert()
    {
        $result = false;
        if ($gdObject = $this->loadBits()) {
            $image = $this->adjustImage($gdObject);
            $result = $this->makePngFromGd($image);

        }
        return $result;
    }

    protected function loadBits()
    {
        if (file_exists($this->sourceFilePath)) {
            $gdObject = imagecreatefrombmp($this->sourceFilePath);
            return $gdObject;
        }
        return false;
    }

    protected function adjustImage($image)
    {
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $rgb = imagecolorat($image, $x, $y);

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $redChannel = round(
                    ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
                );
                $greenChannel = round(
                    ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
                );
                $blueChannel = round(
                    ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
                );

                $color = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->resizeImage($image);
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

    protected function parseScreen($data) { }

    protected function exportData($parsedData, $flashedImage = false) { }
}