<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Timexhrg;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;

final readonly class TimexhrgPixelRenderer
{
    public function renderSingle(ParsedScreen $parsedScreen, ColorTable $colorTable, int $width, int $height): GdImage
    {
        $inkColor = $parsedScreen->attributes->inkMap[0][0];
        $paperColor = $parsedScreen->attributes->paperMap[0][0];
        $image = imagecreatetruecolor($width, $height);

        foreach ($parsedScreen->pixelsData as $rowY => $row) {
            $y = $rowY * 2;
            foreach ($row as $x => $pixel) {
                $zxColor = $pixel === 1 ? $inkColor : $paperColor;
                $color = $colorTable->colors[$zxColor];
                imagesetpixel($image, $x, $y, $color);
                imagesetpixel($image, $x, $y + 1, $color);
            }
        }

        return $image;
    }

    public function renderMerged(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        int $width,
        int $height,
    ): GdImage {
        $ink1 = $parsedScreen1->attributes->inkMap[0][0];
        $paper1 = $parsedScreen1->attributes->paperMap[0][0];
        $ink2 = $parsedScreen2->attributes->inkMap[0][0];
        $paper2 = $parsedScreen2->attributes->paperMap[0][0];

        $image = imagecreatetruecolor($width, $height);

        foreach ($parsedScreen1->pixelsData as $rowY => $row) {
            $y = $rowY * 2;
            foreach ($row as $x => $pixel1) {
                $pixel2 = $parsedScreen2->pixelsData[$rowY][$x];
                $color1 = $pixel1 === 1 ? $ink1 : $paper1;
                $color2 = $pixel2 === 1 ? $ink2 : $paper2;
                $color = $colorTable->gigaColors[($color1 << 4) | $color2];
                imagesetpixel($image, $x, $y, $color);
                imagesetpixel($image, $x, $y + 1, $color);
            }
        }

        return $image;
    }
}
