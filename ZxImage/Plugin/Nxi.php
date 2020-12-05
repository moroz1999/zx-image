<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Nxi extends Standard
{
    /**
     * @var int|null
     */
    protected $strictFileSize = 49664;
    /**
     * @var int
     */
    protected $paletteLength = 256;
    protected $rgb3torgb8 = [
        0 => 0,
        1 => 36,
        2 => 73,
        3 => 109,
        4 => 146,
        5 => 182,
        6 => 219,
        7 => 255,
    ];

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        $pixelsArray = [];
        $paletteArray = [];
        if ($this->makeHandle()) {
            $paletteArray = $this->read16BitStrings($this->paletteLength);
            $pixelsArray = $this->read8BitStrings($this->width * $this->height);
        }
        $resultBits = [
            'pixelsArray' => $pixelsArray,
            'paletteArray' => $paletteArray,
        ];
        return $resultBits;
    }

    protected function parseScreen($data): array
    {
        $parsedData = [];
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseNxiPalette($data['paletteArray']);
        return $parsedData;
    }

    protected function parsePixels(array $pixelsArray): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($pixelsArray as $bit) {
            $pixelsData[$y][$x] = bindec($bit);
            $x++;
            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }

    protected function parseNxiPalette($paletteArray)
    {
        $paletteData = [];
        foreach ($paletteArray as $clutItem) {
            $r = (int)$this->rgb3torgb8[bindec(substr($clutItem, 0, 3))];
            $g = (int)$this->rgb3torgb8[bindec(substr($clutItem, 3, 3))];
            $b = (int)$this->rgb3torgb8[bindec(substr($clutItem, 6, 2) . substr($clutItem, 15, 1))];

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

    protected function exportData(array $parsedData, bool $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => $row) {
            foreach ($row as $x => $pixel) {
                $color = $parsedData['colorsData'][$pixel];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }
}
