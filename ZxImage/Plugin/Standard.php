<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


use GifCreator\GifCreator;

class Standard extends Plugin
{
    /**
     * @return string|null
     */
    public function convert()
    {
        $result = null;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);
            if (!empty($parsedData['attributesData']) && !empty($parsedData['attributesData']['flashMap'])) {
                $gifImages = [];

                $image = $this->exportData($parsedData, false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData, true);
                $gifImages[] = $this->getRightPaletteGif($image);

                $delays = [32, 32];
                $result = $this->buildAnimatedGif($gifImages, $delays);
            } else {
                $image = $this->exportData($parsedData, false);
                $result = $this->makePngFromGd($image);
            }
        }
        return $result;
    }

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        $attributesArray = [];
        if ($this->makeHandle()) {
            $pixelsArray = $this->read8BitStrings(6144);
            while ($bin = $this->read8BitString()) {
                $attributesArray[] = $bin;
            }
            $resultBits = ['pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray];
            return $resultBits;
        }
        return null;
    }

    protected function parseScreen($data): array
    {
        $parsedData = [];
        $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        return $parsedData;
    }

    protected function parseAttributes(array $attributesArray): array
    {
        $x = 0;
        $y = 0;
        $attributesData = ['inkMap' => [], 'paperMap' => [], 'flashMap' => []];
        foreach ($attributesArray as &$bits) {
            $ink = substr($bits, 1, 1) . substr($bits, 5);
            $paper = substr($bits, 1, 4);

            $attributesData['inkMap'][$y][$x] = $ink;
            $attributesData['paperMap'][$y][$x] = $paper;

            $flashStatus = substr($bits, 0, 1);
            if ($flashStatus == '1') {
                $attributesData['flashMap'][$y][$x] = $flashStatus;
            }

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }

    protected function parsePixels(array $pixelsArray): array
    {
        $x = 0;
        $y = 0;
        $zxY = 0;
        $pixelsData = [];
        foreach ($pixelsArray as &$bits) {
            $offset = 0;
            while ($offset < 8) {
                $bit = substr($bits, $offset, 1);

                $pixelsData[$zxY][$x] = $bit;

                $offset++;
                $x++;
                if ($x >= $this->width) {
                    $x = 0;
                    $y++;
                    $zxY = $this->calculateZXY($y);
                }
            }
        }
        return $pixelsData;
    }

    protected function calculateZXY(int $y): int
    {
        $offset = 0;
        if ($y > 127) {
            $offset = 128;
            $y = $y - 128;
        } elseif ($y > 63) {
            $offset = 64;
            $y = $y - 64;
        }
        $rows = (int)($y / 8);
        $rests = $y - $rows * 8;
        return $offset + $rests * 8 + $rows;
    }

    protected function exportData(array $parsedData, bool $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                $mapPositionX = (int)($x / $this->attributeWidth);
                $mapPositionY = (int)($y / $this->attributeHeight);

                if ($flashedImage && isset($parsedData['attributesData']['flashMap'][$mapPositionY][$mapPositionX])) {
                    if ($pixel === '1') {
                        $ZXcolor = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    }
                } else {
                    if ($pixel === '1') {
                        $ZXcolor = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    }
                }
                $color = $this->colors[$ZXcolor];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function getRightPaletteGif($srcImage)
    {
        $palettedImage = imagecreate(imagesx($srcImage), imagesy($srcImage));
        imagecopy($palettedImage, $srcImage, 0, 0, 0, 0, imagesx($srcImage), imagesy($srcImage));
        imagecolormatch($srcImage, $palettedImage);
        return $this->makeGifFromGd($palettedImage);
    }

    protected function buildAnimatedGif($frames, $durations)
    {
        $gc = new GifCreator();
        $gc->create($frames, $durations, 0);

        return $gc->getGif();
    }

    protected function interlaceMix(&$image1, &$image2, $lineHeight)
    {
        $multiplier = 1;
        if ($this->zoom == '3' || $this->zoom == '4') {
            $multiplier = 2;
        }

        $width = imagesx($image1);
        $height = imagesy($image1);

        for ($y = 0; $y < $height; $y++) {
            if ((int)($y / ($lineHeight * $multiplier)) % 2) {
                for ($x = 0; $x < $width; $x++) {
                    $pixel1 = imagecolorat($image1, $x, $y);
                    $pixel2 = imagecolorat($image2, $x, $y);

                    imagesetpixel($image2, $x, $y, $pixel1);
                    imagesetpixel($image1, $x, $y, $pixel2);
                }
            }
        }
    }
}
