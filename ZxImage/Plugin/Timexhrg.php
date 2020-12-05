<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Timexhrg extends Gigascreen
{
    /**
     * @var int|null
     */
    protected $strictFileSize = 12289 * 2;
    /**
     * @var int
     */
    protected $width = 512;
    /**
     * @var int
     */
    protected $height = 384;

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
        $attributesData['flashMap'] = [];
        return $attributesData;
    }

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        $pixelsArray = [];
        if ($this->makeHandle()) {
            $image1 = $this->read8BitStrings(6144);
            $image2 = $this->read8BitStrings(6144);
            $attribute1 = [$this->read8BitString()];
            $image3 = $this->read8BitStrings(6144);
            $image4 = $this->read8BitStrings(6144);
            $attribute2 = [$this->read8BitString()];

            $x = 0;
            $length = $this->width * ($this->height / 2) / 8;
            while (($x < $length)) {
                $pixelsArray[] = $image1[$x / 2];
                $pixelsArray[] = $image2[$x / 2];
                $pixelsArray2[] = $image3[$x / 2];
                $pixelsArray2[] = $image4[$x / 2];
                $x = $x + 2;
            }

            $resultBits = [
                ['pixelsArray' => $pixelsArray, 'attributesArray' => $attribute1],
                ['pixelsArray' => $pixelsArray2, 'attributesArray' => $attribute2],
            ];

            return $resultBits;
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
        $this->border = bindec($parsedData['attributesData']['paperMap']);

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function exportDataMerged($parsedData1, $parsedData2, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData1['pixelsData'] as $rowY => &$row) {
            $y = $rowY * 2;
            foreach ($row as $x => &$pixel1) {
                $pixel2 = $parsedData2['pixelsData'][$rowY][$x];

                if ($pixel1 === '1') {
                    $ZXcolor = $parsedData1['attributesData']['inkMap'];
                } else {
                    $ZXcolor = $parsedData1['attributesData']['paperMap'];
                }

                if ($pixel2 === '1') {
                    $ZXcolor .= $parsedData2['attributesData']['inkMap'];
                } else {
                    $ZXcolor .= $parsedData2['attributesData']['paperMap'];
                }

                $color = $this->gigaColors[$ZXcolor];
                imagesetpixel($image, $x, $y, $color);
                imagesetpixel($image, $x, $y + 1, $color);
            }
        }
        $resultImage = $this->drawBorder($image, $parsedData1);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }


}