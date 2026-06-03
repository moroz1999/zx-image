<?php

declare(strict_types=1);

namespace ZxImage\Service\Image;

use GdImage;

final readonly class InterlaceMixer
{
    public function mix(GdImage $firstImage, GdImage $secondImage, int $lineHeight, float $zoom): void
    {
        $multiplier = ($zoom === 3.0 || $zoom === 4.0) ? 2 : 1;
        $width = imagesx($firstImage);
        $height = imagesy($firstImage);

        for ($y = 0; $y < $height; $y++) {
            if ((int)($y / ($lineHeight * $multiplier)) % 2 === 1) {
                for ($x = 0; $x < $width; $x++) {
                    $pixel1 = imagecolorat($firstImage, $x, $y);
                    $pixel2 = imagecolorat($secondImage, $x, $y);
                    imagesetpixel($secondImage, $x, $y, $pixel1);
                    imagesetpixel($firstImage, $x, $y, $pixel2);
                }
            }
        }
    }
}
