<?php

declare(strict_types=1);

namespace ZxImage\Filter;

use GdImage;
use Override as OverrideAttribute;

final class Scanlines extends Filter
{
    private const int INTERLACE_NUMERATOR = 3;
    private const int INTERLACE_DENOMINATOR = 4;

    #[OverrideAttribute]
    public function apply(GdImage $image, ?GdImage $srcImage = null): GdImage
    {
        $dstWidth = imagesx($image);
        $dstHeight = imagesy($image);

        for ($y = 0; $y < $dstHeight; $y = $y + 2) {
            for ($x = 0; $x < $dstWidth; $x++) {
                $rgb = $this->getPixelColor($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $r = intdiv($r * self::INTERLACE_NUMERATOR + self::INTERLACE_DENOMINATOR - 1, self::INTERLACE_DENOMINATOR);
                $g = intdiv($g * self::INTERLACE_NUMERATOR + self::INTERLACE_DENOMINATOR - 1, self::INTERLACE_DENOMINATOR);
                $b = intdiv($b * self::INTERLACE_NUMERATOR + self::INTERLACE_DENOMINATOR - 1, self::INTERLACE_DENOMINATOR);

                $color = $r * 0x010000 + $g * 0x0100 + $b;

                imagesetpixel($image, $x, $y, $color);
            }
        }

        return $image;
    }
}
