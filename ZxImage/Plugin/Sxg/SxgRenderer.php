<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sxg;

use GdImage;
use RuntimeException;

final readonly class SxgRenderer
{
    /**
     * @param array<int, array<int, int>> $pixelsData
     * @param array<int, int>             $colors
     */
    public function render(array $pixelsData, array $colors, int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                if (isset($colors[$pixel])) {
                    imagesetpixel($image, $x, $y, $colors[$pixel]);
                }
            }
        }

        return $image;
    }
}
