<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Flash extends Standard
{
    /**
     * @return string|null
     */
    public function convert()
    {
        $result = null;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);

            $image = $this->exportData($parsedData);
            $result = $this->makeGifFromGd($image);
        }
        return $result;
    }

    protected function exportData(array $parsedData, bool $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                $mapPositionX = (int)($x / $this->attributeWidth);
                $mapPositionY = (int)($y / $this->attributeHeight);

                if (isset($parsedData['attributesData']['flashMap'][$mapPositionY][$mapPositionX])) {
                    if ($pixel === '1') {
                        $colorZX = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                        $colorZX .= $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                        $color = $this->gigaColors[$colorZX];
                    } else {
                        $colorZX = '0000';
                        $color = $this->colors[$colorZX];
                    }
                } else {
                    if ($pixel === '1') {
                        $colorZX = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $colorZX = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    }
                    $color = $this->colors[$colorZX];
                }

                imagesetpixel($image, $x, $y, $color);
            }
        }
        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

}
