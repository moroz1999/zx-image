<?php

namespace ZxImage\Filter;

class Scanlines extends Filter
{
    protected $interlaceMultiplier = 0.75;

    public function apply($image, $srcImage = false)
    {
        $dstWidth = imagesx($image);
        $dstHeight = imagesy($image);

        for ($y = 0; $y < $dstHeight; $y = $y + 2) {

            for ($x = 0; $x < $dstWidth; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $r = ceil($r * $this->interlaceMultiplier);
                $g = ceil($g * $this->interlaceMultiplier);
                $b = ceil($b * $this->interlaceMultiplier);

                $color = $r * 0x010000 + $g * 0x0100 + $b;

                imagesetpixel($image, $x, $y, $color);
            }
        }

        return $image;
    }
}