<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Bsp;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;

final readonly class BspRenderer
{
    public function __construct(
        private BspBorderRenderer $borderRenderer = new BspBorderRenderer(),
    ) {
    }

    public function renderSingle(
        BspData $bspData,
        ParsedScreen $screen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $geometry,
    ): GdImage {
        $borderIndex = $screen === $bspData->screen1 ? $bspData->border1 : $bspData->border2;
        $center = $this->renderCenter($screen, $colorTable, $flashedImage, $geometry);

        return $this->borderRenderer->applySingle(
            $center,
            $screen,
            $bspData->hasBorderData,
            $borderIndex,
            $colorTable,
            $geometry->width,
            $geometry->height,
            $geometry->borderWidth,
            $geometry->borderHeight,
        );
    }

    public function renderMerged(
        BspData $bspData,
        ParsedScreen $firstScreen,
        ParsedScreen $secondScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $geometry,
    ): GdImage {
        $center = imagecreatetruecolor($geometry->width, $geometry->height);
        foreach ($firstScreen->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel1) {
                $mapX = (int)($x / $geometry->attributeWidth);
                $mapY = (int)($y / $geometry->attributeHeight);
                $pixel2 = $secondScreen->pixelsData[$y][$x];
                $color1 = $this->getZxColor($firstScreen, $pixel1, $mapX, $mapY, $flashedImage);
                $color2 = $this->getZxColor($secondScreen, $pixel2, $mapX, $mapY, $flashedImage);
                imagesetpixel($center, $x, $y, $colorTable->gigaColors[($color1 << 4) | $color2]);
            }
        }

        $screen2Arg = $bspData->hasGigaData ? $secondScreen : null;
        return $this->borderRenderer->applyMerged(
            $center,
            $firstScreen,
            $screen2Arg,
            $bspData->hasBorderData,
            $bspData->border1,
            $bspData->border2,
            $colorTable,
            $geometry->width,
            $geometry->height,
            $geometry->borderWidth,
            $geometry->borderHeight,
        );
    }

    private function renderCenter(
        ParsedScreen $screen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $geometry,
    ): GdImage {
        $center = imagecreatetruecolor($geometry->width, $geometry->height);
        foreach ($screen->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                $mapX = (int)($x / $geometry->attributeWidth);
                $mapY = (int)($y / $geometry->attributeHeight);
                $zxColor = $this->getZxColor($screen, $pixel, $mapX, $mapY, $flashedImage);
                imagesetpixel($center, $x, $y, $colorTable->colors[$zxColor]);
            }
        }

        return $center;
    }

    private function getZxColor(
        ParsedScreen $screen,
        int $pixel,
        int $mapX,
        int $mapY,
        bool $flashedImage,
    ): int {
        if ($flashedImage && isset($screen->attributes->flashMap[$mapY][$mapX])) {
            return $pixel === 1
                ? $screen->attributes->paperMap[$mapY][$mapX]
                : $screen->attributes->inkMap[$mapY][$mapX];
        }

        return $pixel === 1
            ? $screen->attributes->inkMap[$mapY][$mapX]
            : $screen->attributes->paperMap[$mapY][$mapX];
    }
}
