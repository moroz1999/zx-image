<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

class Sam4 extends Standard
{
    use Sam;

    /**
     * @var int|null
     */
    protected $strictFileSize;
    /**
     * @var int
     */
    protected $paletteLength = 16;
    /**
     * @var int
     */
    protected $bitPerPixel = 4;
    /**
     * @var float
     */
    protected $pixelRatio = 1;

    protected function parsePixels(array $pixelsArray): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($pixelsArray as &$bits) {
            $p1 = substr($bits, 0, 4);
            $pixelsData[$y][$x] = $p1;
            $x++;
            $p2 = substr($bits, 4, 4);
            $pixelsData[$y][$x] = $p2;
            $x++;

            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }

    protected function exportData(array $parsedData, bool $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => $pixel) {
                $color = $parsedData['colorsData'][bindec($pixel)];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }
}
