<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;

final readonly class PixelCanvas
{
    /**
     * @param int[][] $pixelsData
     * @param int[] $colors
     */
    public function draw(array $pixelsData, array $colors, int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                imagesetpixel($image, $x, $y, $colors[$pixel]);
            }
        }
        return $image;
    }
}
