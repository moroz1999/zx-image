<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

trait Sam
{
    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        $pixelsArray = [];
        $paletteArray = [];
        if ($this->makeHandle()) {
            $length = 0;
            $divisor = (8 / $this->bitPerPixel) / $this->pixelRatio;
            while ($bin = $this->read8BitString()) {
                if ($length < $this->width * $this->height / $divisor) {
                    $pixelsArray[] = $bin;
                } elseif ($length < $this->width * $this->height / $divisor + $this->paletteLength) {
                    $paletteArray[] = $bin;
                }
                $length++;
            }
            $resultBits = [
                'pixelsArray' => $pixelsArray,
                'paletteArray' => $paletteArray,
            ];
            return $resultBits;
        }
        return null;
    }

    protected function parseScreen($data): array
    {
        $parsedData = [];
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseSam4Palette($data['paletteArray']);
        return $parsedData;
    }

    protected function parseSam4Palette($paletteArray)
    {
        $m = 36;
        $paletteData = [];
        foreach ($paletteArray as &$clutItem) {
            $bright = (int)substr($clutItem, 4, 1);
            $r = ((int)substr($clutItem, 2, 1) * 4 + (int)substr($clutItem, 6, 1) * 2 + $bright) * $m;
            $g = ((int)substr($clutItem, 1, 1) * 4 + (int)substr($clutItem, 5, 1) * 2 + $bright) * $m;
            $b = ((int)substr($clutItem, 3, 1) * 4 + (int)substr($clutItem, 7, 1) * 2 + $bright) * $m;

            $redChannel = (int)round(
                ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
            );
            $greenChannel = (int)round(
                ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
            );
            $blueChannel = (int)round(
                ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
            );

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;

            $paletteData[] = $RGB;
        }
        return $paletteData;
    }
}