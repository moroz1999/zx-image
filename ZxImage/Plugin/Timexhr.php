<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Timexhr extends Standard
{
    /**
     * @var int|null
     */
    protected $strictFileSize = 12289;
    /**
     * @var int
     */
    protected $width = 512;
    /**
     * @var int
     */
    protected $height = 384;

    /**
     * @return string|null
     */
    public function convert()
    {
        $result = null;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);
            $image = $this->exportData($parsedData, false);
            $result = $this->makePngFromGd($image);
        }
        return $result;
    }

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        $pixelsArray = [];
        if ($this->makeHandle()) {
            $attribute = 0;
            $length = 0;
            $pixelsArray1 = [];
            $pixelsArray2 = [];
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray1[] = $bin;
                } elseif ($length < 6144 * 2) {
                    $pixelsArray2[] = $bin;
                } else {
                    $attribute = [$bin];
                }
                $length++;
            }

            $x = 0;
            $length = $this->width * ($this->height / 2) / 8;
            while (($x < $length)) {
                $pixelsArray[] = $pixelsArray1[$x / 2];
                $pixelsArray[] = $pixelsArray2[$x / 2];
                $x = $x + 2;
            }

            return ['pixelsArray' => $pixelsArray, 'attributesArray' => $attribute];
        }
        return null;
    }

    protected function exportData(array $parsedData, bool $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $rowY => &$row) {
            $y = $rowY * 2;
            foreach ($row as $x => &$pixel) {
                if ($pixel === '1') {
                    $ZXcolor = $parsedData['attributesData']['inkMap'];
                } else {
                    $ZXcolor = $parsedData['attributesData']['paperMap'];
                }
                $color = $this->colors[$ZXcolor];
                imagesetpixel($image, $x, $y, $color);
                imagesetpixel($image, $x, $y + 1, $color);
            }
        }
        if ($this->border) {
            $this->border = bindec($parsedData['attributesData']['paperMap']);
            $resultImage = $this->drawBorder($image, $parsedData);
        } else {
            $resultImage = $image;
        }
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function parseAttributes(array $attributesArray): array
    {
        $attributesData = [];
        //Bits 3-5: Sets the screen colour in hi-res mode.
        //000 - Black on White     100 - Green on Magenta
        //001 - Blue on Yellow     101 - Cyan on Red
        //010 - Red on Cyan        110 - Yellow on Blue
        //011 - Magenta on Green   111 - White on Black
        $color = substr(reset($attributesArray), 2, 3);
        switch ($color) {
            case '000':
                $attributesData['inkMap'] = '1000';
                $attributesData['paperMap'] = '1111';
                break;
            case '001':
                $attributesData['inkMap'] = '1001';
                $attributesData['paperMap'] = '1110';
                break;
            case '010':
                $attributesData['inkMap'] = '1010';
                $attributesData['paperMap'] = '1101';
                break;
            case '011':
                $attributesData['inkMap'] = '1011';
                $attributesData['paperMap'] = '1100';
                break;
            case '100':
                $attributesData['inkMap'] = '1100';
                $attributesData['paperMap'] = '1011';
                break;
            case '101':
                $attributesData['inkMap'] = '1101';
                $attributesData['paperMap'] = '1010';
                break;
            case '111':
                $attributesData['inkMap'] = '1111';
                $attributesData['paperMap'] = '1000';
                break;
        }

        return $attributesData;
    }


}