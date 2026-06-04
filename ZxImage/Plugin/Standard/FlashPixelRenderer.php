<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Standard;

use GdImage;
use RuntimeException;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;

final readonly class FlashPixelRenderer
{
    public function render(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        int $width,
        int $height,
        int $attributeWidth,
        int $attributeHeight,
    ): GdImage {
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }

        foreach ($parsedScreen->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                $mapX = intdiv($x, $attributeWidth);
                $mapY = intdiv($y, $attributeHeight);

                $inkKey = $parsedScreen->attributes->inkMap[$mapY][$mapX];
                $paperKey = $parsedScreen->attributes->paperMap[$mapY][$mapX];

                if (isset($parsedScreen->attributes->flashMap[$mapY][$mapX])) {
                    $color = $pixel === 1
                        ? $colorTable->gigaColors[($inkKey << 4) | $paperKey]
                        : $colorTable->colors[0];
                } else {
                    $colorKey = $pixel === 1 ? $inkKey : $paperKey;
                    $color = $colorTable->colors[$colorKey];
                }

                imagesetpixel($image, $x, $y, $color);
            }
        }

        return $image;
    }
}
