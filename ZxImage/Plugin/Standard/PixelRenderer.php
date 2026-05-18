<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Standard;

use GdImage;
use ZxImage\Dto\ParsedScreen;

readonly class PixelRenderer
{
    public function render(
        ParsedScreen $parsedData,
        bool $flashedImage,
        array $colors,
        int $width,
        int $height,
        int $attributeWidth,
        int $attributeHeight
    ): GdImage {
        $image = imagecreatetruecolor($width, $height);

        foreach ($parsedData->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                $mapPositionX = (int)($x / $attributeWidth);
                $mapPositionY = (int)($y / $attributeHeight);

                if ($flashedImage && isset($parsedData->attributes->flashMap[$mapPositionY][$mapPositionX])) {
                    $zxColor = $pixel === 1
                        ? $parsedData->attributes->paperMap[$mapPositionY][$mapPositionX]
                        : $parsedData->attributes->inkMap[$mapPositionY][$mapPositionX];
                } else {
                    $zxColor = $pixel === 1
                        ? $parsedData->attributes->inkMap[$mapPositionY][$mapPositionX]
                        : $parsedData->attributes->paperMap[$mapPositionY][$mapPositionX];
                }

                $color = $colors[$zxColor];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        return $image;
    }
}
