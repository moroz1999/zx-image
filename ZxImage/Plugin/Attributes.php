<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

class Attributes extends Standard
{
    /**
     * @var int|null
     */
    protected $strictFileSize = 768;

    /**
     * @return string|null
     */
    public function convert()
    {
        $result = null;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);
            if (count($parsedData['attributesData']['flashMap']) > 0) {
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
            while ($bin = $this->read8BitString()) {
                $attributesArray[] = $bin;
            }
            $resultBits = ['pixelsArray' => $this->generatePixelsArray(), 'attributesArray' => $attributesArray];
            return $resultBits;
        }
        return null;
    }

    protected function generatePixelsArray()
    {
        $pixelsArray = [];
        for ($third = 0; $third < 3; $third++) {
            for ($y = 0; $y < 4; $y++) {
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = '01010101';
                }
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = '10101010';
                }
            }
        }
        return $pixelsArray;
    }
}
