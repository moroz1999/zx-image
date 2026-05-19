<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Standard;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;

final readonly class HiddenPixelRenderer
{
    private const int HIDDEN_COLOR = 0xFF8000;

    public function render(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        bool $flashedImage,
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

                $inkKey = $parsedScreen->attributes->inkMap[$mapY][$mapX];
                $paperKey = $parsedScreen->attributes->paperMap[$mapY][$mapX];
                $isHidden = false;

                if ($flashedImage && isset($parsedScreen->attributes->flashMap[$mapY][$mapX])) {
                    $colorKey = $pixel === 1 ? $paperKey : $inkKey;
                } elseif ($inkKey === $paperKey && $pixel === 1) {
                    $isHidden = true;
                    $colorKey = 0;
                } else {
                    $colorKey = $pixel === 1 ? $inkKey : $paperKey;
                }

                $color = $isHidden ? self::HIDDEN_COLOR : $colorTable->colors[$colorKey];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        return $image;
    }
}
