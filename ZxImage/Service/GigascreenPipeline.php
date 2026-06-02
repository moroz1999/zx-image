<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;

final readonly class GigascreenPipeline
{
    /**
     * @param callable(): ?DualRawScreen $loadBits
     */
    public function buildFrameSetWithDefaultRenderingFor(
        PluginGeometry $geometry,
        RenderSettings $renderSettings,
        PluginServices $services,
        callable $loadBits,
    ): ?FrameSet {
        return $this->buildFrameSetUsing(
            $loadBits,
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen, $geometry),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderFrame(
                $parsedScreen,
                $colorTable,
                $flashedImage,
                $geometry,
            ),
            fn(ParsedScreen $first, ParsedScreen $second, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderMergedFrame(
                $first,
                $second,
                $colorTable,
                $flashedImage,
                $geometry,
            ),
            $renderSettings,
            $services,
            $geometry,
        );
    }

    /**
     * @param callable(): ?DualRawScreen $loadBits
     * @param callable(RawScreen): ParsedScreen $parseScreen
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderFrame
     * @param callable(ParsedScreen, ParsedScreen, ColorTable, bool): GdImage $renderMergedFrame
     */
    public function buildFrameSetUsing(
        callable $loadBits,
        callable $parseScreen,
        callable $renderFrame,
        callable $renderMergedFrame,
        RenderSettings $renderSettings,
        PluginServices $services,
        PluginGeometry $geometry,
    ): ?FrameSet {
        $dualRawScreen = $loadBits();
        if ($dualRawScreen === null) {
            return null;
        }

        $colorTable = $services->paletteService->buildColorTable($renderSettings->paletteString);
        $parsedScreen1 = $parseScreen($dualRawScreen->first);
        $parsedScreen2 = $parseScreen($dualRawScreen->second);

        $isFlickerMode = $renderSettings->gigascreenMode === 'flicker'
            || $renderSettings->gigascreenMode === 'interlace1'
            || $renderSettings->gigascreenMode === 'interlace2';

        if ($isFlickerMode) {
            return $this->buildFlickerFrameSet(
                $parsedScreen1,
                $parsedScreen2,
                $colorTable,
                $renderSettings,
                $geometry,
                $renderFrame,
            );
        }

        return $this->buildMixedFrameSet(
            $parsedScreen1,
            $parsedScreen2,
            $colorTable,
            $renderSettings,
            $geometry,
            $renderMergedFrame,
        );
    }

    /**
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderFrame
     * @param callable(ParsedScreen, ParsedScreen, ColorTable, bool): GdImage $renderMergedFrame
     */
    public function buildFrameSetFromParsedScreens(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        callable $renderFrame,
        callable $renderMergedFrame,
        RenderSettings $renderSettings,
        PluginGeometry $geometry,
    ): FrameSet {
        $isFlickerMode = $renderSettings->gigascreenMode === 'flicker'
            || $renderSettings->gigascreenMode === 'interlace1'
            || $renderSettings->gigascreenMode === 'interlace2';

        if ($isFlickerMode) {
            return $this->buildFlickerFrameSet(
                $parsedScreen1,
                $parsedScreen2,
                $colorTable,
                $renderSettings,
                $geometry,
                $renderFrame,
            );
        }

        return $this->buildMixedFrameSet(
            $parsedScreen1,
            $parsedScreen2,
            $colorTable,
            $renderSettings,
            $geometry,
            $renderMergedFrame,
        );
    }

    public function parseScreen(RawScreen $rawScreen, PluginGeometry $runtime): ParsedScreen
    {
        $attributes = (new AttributeParser($runtime->width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($runtime->width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes);
    }

    public function renderFrame(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $runtime,
    ): GdImage {
        return (new PixelRenderer())->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $runtime->width,
            $runtime->height,
            $runtime->attributeWidth,
            $runtime->attributeHeight,
        );
    }

    public function renderMergedFrame(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $runtime,
    ): GdImage {
        $image = imagecreatetruecolor($runtime->width, $runtime->height);

        foreach ($parsedScreen1->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel1) {
                $mapX = (int)($x / $runtime->attributeWidth);
                $mapY = (int)($y / $runtime->attributeHeight);
                $pixel2 = $parsedScreen2->pixelsData[$y][$x];

                if ($flashedImage && isset($parsedScreen1->attributes->flashMap[$mapY][$mapX])) {
                    $color1 = $pixel1 === 1
                        ? $parsedScreen1->attributes->paperMap[$mapY][$mapX]
                        : $parsedScreen1->attributes->inkMap[$mapY][$mapX];
                } else {
                    $color1 = $pixel1 === 1
                        ? $parsedScreen1->attributes->inkMap[$mapY][$mapX]
                        : $parsedScreen1->attributes->paperMap[$mapY][$mapX];
                }

                if ($flashedImage && isset($parsedScreen2->attributes->flashMap[$mapY][$mapX])) {
                    $color2 = $pixel2 === 1
                        ? $parsedScreen2->attributes->paperMap[$mapY][$mapX]
                        : $parsedScreen2->attributes->inkMap[$mapY][$mapX];
                } else {
                    $color2 = $pixel2 === 1
                        ? $parsedScreen2->attributes->inkMap[$mapY][$mapX]
                        : $parsedScreen2->attributes->paperMap[$mapY][$mapX];
                }

                imagesetpixel($image, $x, $y, $colorTable->gigaColors[($color1 << 4) | $color2]);
            }
        }

        return $image;
    }

    private function buildFlickerFrameSet(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        RenderSettings $renderSettings,
        PluginGeometry $geometry,
        callable $renderFrame,
    ): FrameSet {
        $hasFlash = count($parsedScreen1->attributes->flashMap) > 0
            || count($parsedScreen2->attributes->flashMap) > 0;

        $frames = [];
        if ($hasFlash) {
            for ($i = 0; $i < 32; $i++) {
                $flashedImage = $i >= 16;
                $screen = ($i & 1) === 1 ? $parsedScreen1 : $parsedScreen2;
                $image = $renderFrame($screen, $colorTable, $flashedImage);
                $frames[] = new Frame($image, 2, $renderSettings);
            }
        } else {
            $image1 = $renderFrame($parsedScreen1, $colorTable, false);
            $frames[] = new Frame($image1, 2, $renderSettings);
            $image2 = $renderFrame($parsedScreen2, $colorTable, false);
            $frames[] = new Frame($image2, 2, $renderSettings);
        }

        return new FrameSet(
            $frames,
            $renderSettings,
            $geometry->toRenderGeometry(),
            $colorTable,
            $this->getInterlaceLineHeight($renderSettings),
        );
    }

    private function buildMixedFrameSet(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        RenderSettings $renderSettings,
        PluginGeometry $geometry,
        callable $renderMergedFrame,
    ): FrameSet {
        $hasFlash = count($parsedScreen1->attributes->flashMap) > 0
            || count($parsedScreen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $image1 = $renderMergedFrame($parsedScreen1, $parsedScreen2, $colorTable, false);
            $settings1 = $renderSettings;
            $image2 = $renderMergedFrame($parsedScreen1, $parsedScreen2, $colorTable, true);
            $settings2 = $renderSettings;

            return new FrameSet(
                [
                    new Frame($image1, 32, $settings1),
                    new Frame($image2, 32, $settings2),
                ],
                $renderSettings,
                $geometry->toRenderGeometry(),
                $colorTable,
            );
        }

        $image = $renderMergedFrame($parsedScreen1, $parsedScreen2, $colorTable, false);
        $settings = $renderSettings;

        return new FrameSet(
            [new Frame($image, 0, $settings)],
            $renderSettings,
            $geometry->toRenderGeometry(),
            $colorTable,
        );
    }

    private function getInterlaceLineHeight(RenderSettings $renderSettings): ?int
    {
        if ($renderSettings->gigascreenMode === 'interlace1') {
            return 1;
        }

        if ($renderSettings->gigascreenMode === 'interlace2') {
            return 2;
        }

        return null;
    }

}
