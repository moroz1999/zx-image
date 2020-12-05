<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

class SsxRaw extends Plugin
{
    /**
     * @var int|null
     */
    protected $strictFileSize = 98304;
    /**
     * @var int
     */
    protected $width = 512;
    /**
     * @var int
     */
    protected $height = 192;

    protected function exportData(array $parsedData, bool $flashedImage = false)
    {
        $m = 36;

        $image = imagecreatetruecolor($this->width, $this->height * 2);
        foreach ($parsedData['pixelsData'] as $rowY => $row) {
            $y = $rowY * 2;
            foreach ($row as $x => $clutItem) {
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

                imagesetpixel($image, $x, $y, $RGB);
                imagesetpixel($image, $x, $y + 1, $RGB);
            }
        }

        $resultImage = $this->resizeImage($image);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }


    protected function parseScreen($data): array
    {
        $parsedData = [];
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        return $parsedData;
    }

    protected function parsePixels(array $pixelsArray): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($pixelsArray as $pixel) {
            $pixelsData[$y][$x] = $pixel;
            $x++;
            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }


    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        if ($this->makeHandle()) {
            return [
                'pixelsArray' => $this->read8BitStrings($this->strictFileSize),
            ];
        }
        return null;
    }
}
