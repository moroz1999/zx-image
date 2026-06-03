<?php

declare(strict_types=1);

namespace ZxImage\Filter;

use GdImage;
use RuntimeException;

abstract class Filter
{
    public function apply(GdImage $image, ?GdImage $srcImage = null): GdImage
    {
        return $image;
    }

    protected function createImage(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }

        return $image;
    }

    protected function getPixelColor(GdImage $image, int $x, int $y): int
    {
        $color = imagecolorat($image, $x, $y);
        if ($color === false) {
            throw new RuntimeException('Unable to read GD pixel');
        }

        return $color;
    }
}
