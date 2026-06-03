<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Plugin\Standard\PixelRenderer;

final readonly class GigascreenFrameRenderer
{
    public function renderSingle(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $geometry,
    ): GdImage {
        return (new PixelRenderer())->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $geometry->width,
            $geometry->height,
            $geometry->attributeWidth,
            $geometry->attributeHeight,
        );
    }

    public function renderMerged(
        ParsedScreen $firstScreen,
        ParsedScreen $secondScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $geometry,
    ): GdImage {
        $image = imagecreatetruecolor($geometry->width, $geometry->height);

        foreach ($firstScreen->pixelsData as $y => $row) {
            foreach ($row as $x => $firstPixel) {
                $mapX = (int)($x / $geometry->attributeWidth);
                $mapY = (int)($y / $geometry->attributeHeight);
                $secondPixel = $secondScreen->pixelsData[$y][$x];
                $firstColor = $this->resolveColor($firstScreen, $firstPixel, $mapX, $mapY, $flashedImage);
                $secondColor = $this->resolveColor($secondScreen, $secondPixel, $mapX, $mapY, $flashedImage);

                imagesetpixel($image, $x, $y, $colorTable->gigaColors[($firstColor << 4) | $secondColor]);
            }
        }

        return $image;
    }

    private function resolveColor(
        ParsedScreen $parsedScreen,
        int $pixel,
        int $mapX,
        int $mapY,
        bool $flashedImage,
    ): int {
        if ($flashedImage && isset($parsedScreen->attributes->flashMap[$mapY][$mapX])) {
            return $pixel === 1
                ? $parsedScreen->attributes->paperMap[$mapY][$mapX]
                : $parsedScreen->attributes->inkMap[$mapY][$mapX];
        }

        return $pixel === 1
            ? $parsedScreen->attributes->inkMap[$mapY][$mapX]
            : $parsedScreen->attributes->paperMap[$mapY][$mapX];
    }
}
