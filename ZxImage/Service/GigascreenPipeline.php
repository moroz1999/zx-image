<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;

final readonly class GigascreenPipeline
{
    /**
     * @param callable(): ?DualRawScreen $loadBits
     */
    public function convertWithDefaultRendering(PluginRuntime $runtime, callable $loadBits): ?string
    {
        return $this->convertUsing(
            $runtime,
            $loadBits,
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen, $runtime),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderImage(
                $parsedScreen,
                $colorTable,
                $flashedImage,
                $runtime,
            ),
            fn(ParsedScreen $first, ParsedScreen $second, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderMergedImage(
                $first,
                $second,
                $colorTable,
                $flashedImage,
                $runtime,
            ),
        );
    }

    /**
     * @param callable(): ?DualRawScreen $loadBits
     * @param callable(RawScreen): ParsedScreen $parseScreen
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderImage
     * @param callable(ParsedScreen, ParsedScreen, ColorTable, bool): GdImage $renderMergedImage
     */
    public function convertUsing(
        PluginRuntime $runtime,
        callable $loadBits,
        callable $parseScreen,
        callable $renderImage,
        callable $renderMergedImage,
    ): ?string {
        $dualRawScreen = $loadBits();
        if ($dualRawScreen === null) {
            return null;
        }

        $colorTable = $runtime->paletteService->buildColorTable($runtime->paletteString);
        $parsedScreen1 = $parseScreen($dualRawScreen->first);
        $parsedScreen2 = $parseScreen($dualRawScreen->second);

        $isFlickerMode = $runtime->gigascreenMode === 'flicker'
            || $runtime->gigascreenMode === 'interlace1'
            || $runtime->gigascreenMode === 'interlace2';

        if ($isFlickerMode) {
            return $this->buildFlickerAnimation($parsedScreen1, $parsedScreen2, $colorTable, $runtime, $renderImage);
        }

        return $this->buildMixedResult($parsedScreen1, $parsedScreen2, $colorTable, $runtime, $renderMergedImage);
    }

    /**
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderImage
     * @param callable(ParsedScreen, ParsedScreen, ColorTable, bool): GdImage $renderMergedImage
     */
    public function buildFromParsedScreens(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        PluginRuntime $runtime,
        callable $renderImage,
        callable $renderMergedImage,
    ): string {
        $isFlickerMode = $runtime->gigascreenMode === 'flicker'
            || $runtime->gigascreenMode === 'interlace1'
            || $runtime->gigascreenMode === 'interlace2';

        if ($isFlickerMode) {
            return $this->buildFlickerAnimation($parsedScreen1, $parsedScreen2, $colorTable, $runtime, $renderImage);
        }

        return $this->buildMixedResult($parsedScreen1, $parsedScreen2, $colorTable, $runtime, $renderMergedImage);
    }

    public function parseScreen(RawScreen $rawScreen, PluginRuntime $runtime): ParsedScreen
    {
        $attributes = (new AttributeParser($runtime->width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($runtime->width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes);
    }

    public function renderImage(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginRuntime $runtime,
    ): GdImage {
        $image = (new PixelRenderer())->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $runtime->width,
            $runtime->height,
            $runtime->attributeWidth,
            $runtime->attributeHeight,
        );

        return $this->finalizeImage($image, $colorTable, $runtime);
    }

    public function renderMergedImage(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginRuntime $runtime,
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

        return $this->finalizeImage($image, $colorTable, $runtime);
    }

    public function finalizeImage(GdImage $image, ColorTable $colorTable, PluginRuntime $runtime): GdImage
    {
        $image = $runtime->imageProcessor->applyBorder(
            $image,
            $runtime->border,
            $colorTable,
            $runtime->width,
            $runtime->height,
            $runtime->borderWidth,
            $runtime->borderHeight,
            $runtime->usesBorder,
        );
        $image = $runtime->imageProcessor->resize($image, $runtime->zoom, $runtime->preFilters, $runtime->postFilters);
        return $runtime->imageProcessor->rotate($image, $runtime->rotation);
    }

    private function buildFlickerAnimation(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        PluginRuntime $runtime,
        callable $renderImage,
    ): string {
        $hasFlash = count($parsedScreen1->attributes->flashMap) > 0
            || count($parsedScreen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $image1 = $renderImage($parsedScreen1, $colorTable, false);
            $image2 = $renderImage($parsedScreen2, $colorTable, false);
            $image1f = $renderImage($parsedScreen1, $colorTable, true);
            $image2f = $renderImage($parsedScreen2, $colorTable, true);

            $this->applyInterlace($image1, $image2, $runtime);
            $this->applyInterlace($image1f, $image2f, $runtime);

            $frame1 = $runtime->imageEncoder->toPaletteGif($image1);
            $frame2 = $runtime->imageEncoder->toPaletteGif($image2);
            $frame1f = $runtime->imageEncoder->toPaletteGif($image1f);
            $frame2f = $runtime->imageEncoder->toPaletteGif($image2f);

            $gifImages = [];
            $delays = [];
            for ($i = 0; $i < 32; $i++) {
                $gifImages[] = $i < 16
                    ? (($i & 1) ? $frame1 : $frame2)
                    : (($i & 1) ? $frame1f : $frame2f);
                $delays[] = 2;
            }
        } else {
            $image1 = $renderImage($parsedScreen1, $colorTable, false);
            $image2 = $renderImage($parsedScreen2, $colorTable, false);

            $this->applyInterlace($image1, $image2, $runtime);

            $gifImages = [
                $runtime->imageEncoder->toPaletteGif($image1),
                $runtime->imageEncoder->toPaletteGif($image2),
            ];
            $delays = [2, 2];
        }

        $runtime->resultMime = 'image/gif';
        return $runtime->imageEncoder->toAnimatedGif($gifImages, $delays);
    }

    private function buildMixedResult(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        PluginRuntime $runtime,
        callable $renderMergedImage,
    ): string {
        $hasFlash = count($parsedScreen1->attributes->flashMap) > 0
            || count($parsedScreen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $frame1 = $runtime->imageEncoder->toPaletteGif($renderMergedImage($parsedScreen1, $parsedScreen2, $colorTable, false));
            $frame2 = $runtime->imageEncoder->toPaletteGif($renderMergedImage($parsedScreen1, $parsedScreen2, $colorTable, true));
            $runtime->resultMime = 'image/gif';
            return $runtime->imageEncoder->toAnimatedGif([$frame1, $frame2], [32, 32]);
        }

        $image = $renderMergedImage($parsedScreen1, $parsedScreen2, $colorTable, false);
        $runtime->resultMime = 'image/png';
        return $runtime->imageEncoder->toPng($image);
    }

    private function applyInterlace(GdImage $image1, GdImage $image2, PluginRuntime $runtime): void
    {
        if ($runtime->gigascreenMode === 'interlace1') {
            $runtime->imageProcessor->interlaceMix($image1, $image2, 1, $runtime->zoom);
        } elseif ($runtime->gigascreenMode === 'interlace2') {
            $runtime->imageProcessor->interlaceMix($image1, $image2, 2, $runtime->zoom);
        }
    }
}
