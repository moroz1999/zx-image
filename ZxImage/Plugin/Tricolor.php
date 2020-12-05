<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Tricolor extends Standard
{
    /**
     * @var int|null
     */
    protected $strictFileSize = 18432;

    /**
     * @return string|null
     */
    public function convert()
    {
        $result = null;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);

            if ($this->gigascreenMode == 'flicker') {
                $gifImages = [];
                $image = $this->exportData($parsedData[0], false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData[1], false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData[2], false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $delays = [2, 2, 2];

                $result = $this->buildAnimatedGif($gifImages, $delays);
            } else {
                $resources = [];
                $resources[] = $this->exportData($parsedData[0], false);
                $resources[] = $this->exportData($parsedData[1], false);
                $resources[] = $this->exportData($parsedData[2], false);

                $result = $this->buildMixedPng($resources);
            }
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
            $length = 0;
            $image = 0;
            while ($bin = $this->read8BitString()) {
                if ($length == 6144) {
                    $length = 0;
                    $image++;
                    $pixelsArray[$image] = [];
                }
                $pixelsArray[$image][] = $bin;
                $length++;
            }
            $resultBits = ['pixelsArray' => $pixelsArray];
            return $resultBits;
        }
        return null;
    }

    protected function parseScreen($data): array
    {
        $parsedData = [];
        $parsedData[0]['pixelsData'] = $this->parsePixels($data['pixelsArray'][0]);
        $parsedData[0]['attributesData'] = $this->generateAttributesArray('1010', '0000');
        $parsedData[1]['pixelsData'] = $this->parsePixels($data['pixelsArray'][1]);
        $parsedData[1]['attributesData'] = $this->generateAttributesArray('1100', '0000');
        $parsedData[2]['pixelsData'] = $this->parsePixels($data['pixelsArray'][2]);
        $parsedData[2]['attributesData'] = $this->generateAttributesArray('1001', '0000');
        return $parsedData;
    }

    protected function generateAttributesArray($inkColorCode, $paperColorCode)
    {
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

    protected function buildMixedPng($resources): string
    {
        $first = reset($resources);
        $width = imagesx($first);
        $height = imagesy($first);
        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $overall = 0;
                foreach ($resources as &$resource) {
                    $color = imagecolorat($resource, $x, $y);
                    $overall = $overall + $color;
                }
                imagesetpixel($image, $x, $y, $overall);
            }
        }
        $result = $this->makePngFromGd($image);
        return $result;
    }
}
