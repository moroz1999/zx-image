<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Standard;

use GdImage;
use RuntimeException;
use ZxImage\Dto\ParsedScreen;

final readonly class PixelRenderer
{
    /**
     * @param array<int, int> $colors
     */
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
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }

        foreach ($parsedData->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                $mapPositionX = intdiv($x, $attributeWidth);
                $mapPositionY = intdiv($y, $attributeHeight);

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
