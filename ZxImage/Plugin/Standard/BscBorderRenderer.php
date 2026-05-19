<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Standard;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;

final readonly class BscBorderRenderer
{
    public function render(
        GdImage $centerImage,
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        ?int $border,
        int $width,
        int $height,
        int $borderWidth,
        int $borderHeight,
    ): GdImage {
        if ($border === null) {
            return $centerImage;
        }

        $resultImage = imagecreatetruecolor(
            $width + $borderWidth * 2,
            $height + $borderHeight * 2,
        );

        $x = 0;
        $y = 0;

        foreach ($parsedScreen->borderData as $byte) {
            $leftColor = $byte & 0x07;
            $color = $colorTable->colors[$leftColor];
            for ($i = 0; $i < 8; $i++) {
                imagesetpixel($resultImage, $x + $i, $y, $color);
            }

            $x += 8;
            $rightColor = ($byte >> 3) & 0x07;
            $color = $colorTable->colors[$rightColor];
            for ($i = 0; $i < 8; $i++) {
                imagesetpixel($resultImage, $x + $i, $y, $color);
            }

            $x += 8;
            if ($y >= ($borderHeight + 8) && $y < ($height + $borderHeight + 8) && $x === $borderWidth) {
                $x += $width;
            }

            if ($x >= $width + $borderWidth * 2) {
                $x = 0;
                $y++;
            }
        }

        imagecopy($resultImage, $centerImage, $borderWidth, $borderHeight + 8, 0, 0, $width, $height);
        return $resultImage;
    }
}
