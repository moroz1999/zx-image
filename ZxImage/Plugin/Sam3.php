<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Sam3 extends Standard
{
    use Sam;

    /**
     * @var int|null
     */
    protected $strictFileSize;
    /**
     * @var int
     */
    protected $width = 512;
    /**
     * @var int
     */
    protected $height = 384;
    /**
     * @var int
     */
    protected $paletteLength = 4;
    /**
     * @var int
     */
    protected $bitPerPixel = 2;
    /**
     * @var float
     */
    protected $pixelRatio = 0.5;

    protected function parsePixels(array $pixelsArray): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($pixelsArray as &$bits) {
            $p1 = substr($bits, 0, 2);
            $pixelsData[$y][$x] = $p1;
            $x++;
            $p2 = substr($bits, 2, 2);
            $pixelsData[$y][$x] = $p2;
            $x++;
            $p3 = substr($bits, 4, 2);
            $pixelsData[$y][$x] = $p3;
            $x++;
            $p4 = substr($bits, 6, 2);
            $pixelsData[$y][$x] = $p4;
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
                imagesetpixel($image, $x, $y * 2, $color);
                imagesetpixel($image, $x, $y * 2 + 1, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }
}
