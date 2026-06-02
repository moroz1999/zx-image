<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\BspData;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RenderGeometry;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Bsp\BspBorderRenderer;
use ZxImage\Plugin\Bsp\BspLoader;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginRuntime;

class Bsp implements FramePluginInterface
{
    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents);
        $this->runtime->borderWidth = 64;
        $this->runtime->borderHeight = 64;
    }

    public function configure(RenderSettings $settings): void
    {
        $this->runtime->applyRenderSettings($settings);
    }

    public function convertFrames(): ?FrameSet
    {
        $bspData = (new BspLoader())->load($this->runtime);
        if ($bspData === null) {
            return null;
        }

        $colorTable = $this->runtime->services->paletteService->buildColorTable($this->runtime->renderSettings->paletteString);
        $pipeline = new GigascreenPipeline();
        $frameSet = $this->buildFrameSet($bspData, $colorTable, $pipeline);

        return new FrameSet(
            $frameSet->frames,
            $frameSet->renderSettings,
            $this->getFrameGeometry(),
            $frameSet->colorTable,
            $frameSet->interlaceLineHeight,
        );
    }

    private function buildFrameSet(
        BspData $bspData,
        ColorTable $colorTable,
        GigascreenPipeline $pipeline,
    ): FrameSet {
        $borderRenderer = new BspBorderRenderer();

        $renderSingle = function (ParsedScreen $screen, ColorTable $ct, bool $flashedImage) use ($bspData, $borderRenderer): GdImage {
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
            return $borderRenderer->applySingle($center, $screen, $bspData->hasBorderData, $borderIndex, $ct, $this->runtime->width, $this->runtime->height, $this->runtime->borderWidth, $this->runtime->borderHeight);
        };

        $renderMerged = function (ParsedScreen $s1, ParsedScreen $s2, ColorTable $ct, bool $flashedImage) use ($bspData, $borderRenderer): GdImage {
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
            return $borderRenderer->applyMerged($center, $s1, $screen2Arg, $bspData->hasBorderData, $bspData->border1, $bspData->border2, $ct, $this->runtime->width, $this->runtime->height, $this->runtime->borderWidth, $this->runtime->borderHeight);
        };

        return $pipeline->buildFrameSetFromParsedScreens($bspData->screen1, $bspData->screen2, $colorTable, $this->runtime, $renderSingle, $renderMerged);
    }

    private function getFrameGeometry(): RenderGeometry
    {
        return new RenderGeometry(
            $this->runtime->width + $this->runtime->borderWidth * 2,
            $this->runtime->height + $this->runtime->borderHeight + 48,
            0,
            0,
            false,
        );
    }
}
