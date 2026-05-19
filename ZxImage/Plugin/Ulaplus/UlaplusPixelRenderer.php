<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Ulaplus;

use GdImage;
use ZxImage\Dto\ParsedScreen;

final readonly class UlaplusPixelRenderer
{
    public function render(
        ParsedScreen $parsedScreen,
        int $width,
        int $height,
        int $attributeWidth,
        int $attributeHeight,
    ): GdImage {
        $image = imagecreatetruecolor($width, $height);

        foreach ($parsedScreen->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                $mapX = (int)($x / $attributeWidth);
                $mapY = (int)($y / $attributeHeight);

                $zxColor = $pixel === 1
                    ? $parsedScreen->attributes->inkMap[$mapY][$mapX]
                    : $parsedScreen->attributes->paperMap[$mapY][$mapX];

                imagesetpixel($image, $x, $y, $parsedScreen->colorOverrides[$zxColor]);
            }
        }

        return $image;
    }
}
