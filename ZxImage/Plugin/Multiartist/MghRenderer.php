<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Multiartist;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Service\PluginServices;

final readonly class MghRenderer
{
    public function __construct(
        private MghBorderRenderer $borderRenderer = new MghBorderRenderer(),
    ) {
    }

    public function renderSingle(
        ParsedScreen $screen,
        ParsedScreen $firstScreen,
        MghBorders $borders,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $geometry,
        PluginServices $services,
    ): GdImage {
        $borderIndex = $screen === $firstScreen ? $borders->border1 : $borders->border2;
        $center = $this->renderCenter($screen, $colorTable, $flashedImage, $geometry);

        return $services->imageProcessor->applyBorder(
            $center,
            $borderIndex,
            $colorTable,
            $geometry->width,
            $geometry->height,
            $geometry->borderWidth,
            $geometry->borderHeight,
            $geometry->usesBorder,
        );
    }

    public function renderMerged(
        ParsedScreen $firstScreen,
        ParsedScreen $secondScreen,
        MghBorders $borders,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $geometry,
        PluginServices $services,
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

        return $this->borderRenderer->apply(
            $center,
            $borders->border1,
            $borders->border2,
            $colorTable,
            $geometry,
            $services,
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
