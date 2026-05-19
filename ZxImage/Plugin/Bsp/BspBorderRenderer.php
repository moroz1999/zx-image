<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Bsp;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;

final readonly class BspBorderRenderer
{
    private const int BORDER_HEIGHT_BOTTOM = 48;

    public function applySingle(
        GdImage $centerImage,
        ParsedScreen $screen,
        bool $hasBorderData,
        int $border1,
        ColorTable $colorTable,
        int $width,
        int $height,
        int $borderWidth,
        int $borderHeight,
    ): GdImage {
        $totalWidth = $width + $borderWidth * 2;
        $totalHeight = $height + $borderHeight + self::BORDER_HEIGHT_BOTTOM;
        $result = imagecreatetruecolor($totalWidth, $totalHeight);

        for ($y = 0; $y < $totalHeight; $y++) {
            for ($x = 0; $x < $totalWidth; $x++) {
                if ($hasBorderData) {
                    if (isset($screen->borderData[$y][$x])) {
                        imagesetpixel($result, $x, $y, $colorTable->colors[$screen->borderData[$y][$x]]);
                    } else {
                        imagesetpixel($result, $x, $y, $colorTable->colors[$border1]);
                    }
                }
            }
        }

        imagecopy($result, $centerImage, $borderWidth, $borderHeight, 0, 0, $width, $height);
        return $result;
    }

    public function applyMerged(
        GdImage $centerImage,
        ParsedScreen $screen1,
        ?ParsedScreen $screen2,
        bool $hasBorderData,
        int $border1,
        int $border2,
        ColorTable $colorTable,
        int $width,
        int $height,
        int $borderWidth,
        int $borderHeight,
    ): GdImage {
        $totalWidth = $width + $borderWidth * 2;
        $totalHeight = $height + $borderHeight + self::BORDER_HEIGHT_BOTTOM;
        $result = imagecreatetruecolor($totalWidth, $totalHeight);

        for ($y = 0; $y < $totalHeight; $y++) {
            for ($x = 0; $x < $totalWidth; $x++) {
                if ($hasBorderData) {
                    $has1 = isset($screen1->borderData[$y][$x]);
                    $has2 = $screen2 !== null && isset($screen2->borderData[$y][$x]);
                    if ($has1 || $has2) {
                        $c1 = $has1 ? $screen1->borderData[$y][$x] : 0;
                        $c2 = ($has2 && $screen2 !== null) ? $screen2->borderData[$y][$x] : 0;
                        imagesetpixel($result, $x, $y, $colorTable->gigaColors[($c1 << 4) | $c2]);
                    }
                } else {
                    imagesetpixel($result, $x, $y, $colorTable->gigaColors[($border1 << 4) | $border2]);
                }
            }
        }

        imagecopy($result, $centerImage, $borderWidth, $borderHeight, 0, 0, $width, $height);
        return $result;
    }
}
