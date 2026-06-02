<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sxg;

use GdImage;

final readonly class SxgRenderer
{
    /**
     * @param int[][] $pixelsData
     * @param int[] $colors
     */
    public function render(array $pixelsData, array $colors, int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
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
