<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use RuntimeException;

final readonly class PixelCanvas
{
    /**
     * @param array<int, array<int, int>> $pixelsData
     * @param array<int, int>             $colors
     */
    public function draw(array $pixelsData, array $colors, int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                imagesetpixel($image, $x, $y, $colors[$pixel]);
            }
        }
        return $image;
    }
}
