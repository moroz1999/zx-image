<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Bsp\BspBorderRenderer;
use ZxImage\Plugin\Bsp\BspLoader;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginRuntime;

class Bsp implements PluginInterface
{
    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->borderWidth = 64;
        $this->runtime->borderHeight = 64;
    }

    public function convert(): ?string
    {
        $bspData = (new BspLoader())->load($this->runtime);
        if ($bspData === null) {
            return null;
        }

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        $pipeline = new GigascreenPipeline();
        $borderRenderer = new BspBorderRenderer();

        $renderSingle = function (ParsedScreen $screen, ColorTable $ct, bool $flashedImage) use ($bspData, $borderRenderer): \GdImage {
            $borderIndex = $screen === $bspData->screen1 ? $bspData->border1 : $bspData->border2;
            $center = imagecreatetruecolor($this->runtime->width, $this->runtime->height);
            foreach ($screen->pixelsData as $y => $row) {
                foreach ($row as $x => $pixel) {
                    $mapX = (int)($x / $this->runtime->attributeWidth);
                    $mapY = (int)($y / $this->runtime->attributeHeight);
                    if ($flashedImage && isset($screen->attributes->flashMap[$mapY][$mapX])) {
                        $zxColor = $pixel === 1
                            ? $screen->attributes->paperMap[$mapY][$mapX]
                            : $screen->attributes->inkMap[$mapY][$mapX];
                    } else {
                        $zxColor = $pixel === 1
                            ? $screen->attributes->inkMap[$mapY][$mapX]
                            : $screen->attributes->paperMap[$mapY][$mapX];
                    }
                    imagesetpixel($center, $x, $y, $ct->colors[$zxColor]);
                }
            }
            $image = $borderRenderer->applySingle($center, $screen, $bspData->hasBorderData, $borderIndex, $ct, $this->runtime->width, $this->runtime->height, $this->runtime->borderWidth, $this->runtime->borderHeight);
            $image = $this->runtime->imageProcessor->resize($image, $this->runtime->zoom, $this->runtime->preFilters, $this->runtime->postFilters);
            return $this->runtime->imageProcessor->rotate($image, $this->runtime->rotation);
        };

        $renderMerged = function (ParsedScreen $s1, ParsedScreen $s2, ColorTable $ct, bool $flashedImage) use ($bspData, $borderRenderer): \GdImage {
            $center = imagecreatetruecolor($this->runtime->width, $this->runtime->height);
            foreach ($s1->pixelsData as $y => $row) {
                foreach ($row as $x => $pixel1) {
                    $mapX = (int)($x / $this->runtime->attributeWidth);
                    $mapY = (int)($y / $this->runtime->attributeHeight);
                    $pixel2 = $s2->pixelsData[$y][$x];
                    if ($flashedImage && isset($s1->attributes->flashMap[$mapY][$mapX])) {
                        $color1 = $pixel1 === 1 ? $s1->attributes->paperMap[$mapY][$mapX] : $s1->attributes->inkMap[$mapY][$mapX];
                    } else {
                        $color1 = $pixel1 === 1 ? $s1->attributes->inkMap[$mapY][$mapX] : $s1->attributes->paperMap[$mapY][$mapX];
                    }
                    if ($flashedImage && isset($s2->attributes->flashMap[$mapY][$mapX])) {
                        $color2 = $pixel2 === 1 ? $s2->attributes->paperMap[$mapY][$mapX] : $s2->attributes->inkMap[$mapY][$mapX];
                    } else {
                        $color2 = $pixel2 === 1 ? $s2->attributes->inkMap[$mapY][$mapX] : $s2->attributes->paperMap[$mapY][$mapX];
                    }
                    imagesetpixel($center, $x, $y, $ct->gigaColors[($color1 << 4) | $color2]);
                }
            }
            $screen2Arg = $bspData->hasGigaData ? $s2 : null;
            $image = $borderRenderer->applyMerged($center, $s1, $screen2Arg, $bspData->hasBorderData, $bspData->border1, $bspData->border2, $ct, $this->runtime->width, $this->runtime->height, $this->runtime->borderWidth, $this->runtime->borderHeight);
            $image = $this->runtime->imageProcessor->resize($image, $this->runtime->zoom, $this->runtime->preFilters, $this->runtime->postFilters);
            return $this->runtime->imageProcessor->rotate($image, $this->runtime->rotation);
        };

        return $pipeline->buildFromParsedScreens($bspData->screen1, $bspData->screen2, $colorTable, $this->runtime, $renderSingle, $renderMerged);
    }

    public function setBorder(?int $border = null): void
    {
        $this->runtime->setBorder($border);
    }

    public function setZoom(float $zoom): void
    {
        $this->runtime->setZoom($zoom);
    }

    public function setRotation(int $rotation): void
    {
        $this->runtime->setRotation($rotation);
    }

    public function setGigascreenMode(string $mode): void
    {
        $this->runtime->setGigascreenMode($mode);
    }

    public function setPalette(string $palette): void
    {
        $this->runtime->setPalette($palette);
    }

    public function setPreFilters(array $filters): void
    {
        $this->runtime->setPreFilters($filters);
    }

    public function setPostFilters(array $filters): void
    {
        $this->runtime->setPostFilters($filters);
    }

    public function setBasePath(string $basePath): void
    {
        $this->runtime->setBasePath($basePath);
    }

    public function getResultMime(): ?string
    {
        return $this->runtime->getResultMime();
    }
}
