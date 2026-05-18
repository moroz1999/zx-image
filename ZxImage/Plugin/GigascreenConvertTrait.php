<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;

trait GigascreenConvertTrait
{
    use PluginConfigTrait;

    abstract protected function loadBits(): ?DualRawScreen;

    public function convert(): ?string
    {
        $dualRawScreen = $this->loadBits();
        if ($dualRawScreen === null) {
            return null;
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        $parsedScreen1 = $this->parseScreen($dualRawScreen->first);
        $parsedScreen2 = $this->parseScreen($dualRawScreen->second);

        $isFlickerMode = $this->gigascreenMode === 'flicker'
            || $this->gigascreenMode === 'interlace1'
            || $this->gigascreenMode === 'interlace2';

        if ($isFlickerMode) {
            return $this->buildFlickerAnimation($parsedScreen1, $parsedScreen2, $colorTable);
        }

        return $this->buildMixedResult($parsedScreen1, $parsedScreen2, $colorTable);
    }

    protected function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = (new AttributeParser($this->width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($this->width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes);
    }

    protected function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $renderer = new PixelRenderer();
        $image = $renderer->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $this->width,
            $this->height,
            $this->attributeWidth,
            $this->attributeHeight,
        );

        $image = $this->imageProcessor->applyBorder(
            $image,
            $this->border,
            $colorTable,
            $this->width,
            $this->height,
            $this->borderWidth,
            $this->borderHeight,
            $this->usesBorder,
        );

        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }

    protected function exportDataMerged(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        bool $flashedImage,
    ): GdImage {
        $image = imagecreatetruecolor($this->width, $this->height);

        foreach ($parsedScreen1->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel1) {
                $mapX = (int)($x / $this->attributeWidth);
                $mapY = (int)($y / $this->attributeHeight);
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

        $image = $this->imageProcessor->applyBorder(
            $image,
            $this->border,
            $colorTable,
            $this->width,
            $this->height,
            $this->borderWidth,
            $this->borderHeight,
            $this->usesBorder,
        );
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }

    private function buildFlickerAnimation(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
    ): string {
        $hasFlash = count($parsedScreen1->attributes->flashMap) > 0
            || count($parsedScreen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $image1 = $this->renderImage($parsedScreen1, $colorTable, false);
            $image2 = $this->renderImage($parsedScreen2, $colorTable, false);
            $image1f = $this->renderImage($parsedScreen1, $colorTable, true);
            $image2f = $this->renderImage($parsedScreen2, $colorTable, true);

            $this->applyInterlace($image1, $image2);
            $this->applyInterlace($image1f, $image2f);

            $frame1 = $this->imageEncoder->toPaletteGif($image1);
            $frame2 = $this->imageEncoder->toPaletteGif($image2);
            $frame1f = $this->imageEncoder->toPaletteGif($image1f);
            $frame2f = $this->imageEncoder->toPaletteGif($image2f);

            $gifImages = [];
            $delays = [];
            for ($i = 0; $i < 32; $i++) {
                $gifImages[] = $i < 16
                    ? (($i & 1) ? $frame1 : $frame2)
                    : (($i & 1) ? $frame1f : $frame2f);
                $delays[] = 2;
            }
        } else {
            $image1 = $this->renderImage($parsedScreen1, $colorTable, false);
            $image2 = $this->renderImage($parsedScreen2, $colorTable, false);

            $this->applyInterlace($image1, $image2);

            $gifImages = [
                $this->imageEncoder->toPaletteGif($image1),
                $this->imageEncoder->toPaletteGif($image2),
            ];
            $delays = [2, 2];
        }

        $this->resultMime = 'image/gif';
        return $this->imageEncoder->toAnimatedGif($gifImages, $delays);
    }

    private function buildMixedResult(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
    ): string {
        $hasFlash = count($parsedScreen1->attributes->flashMap) > 0
            || count($parsedScreen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $frame1 = $this->imageEncoder->toPaletteGif($this->exportDataMerged($parsedScreen1, $parsedScreen2, $colorTable, false));
            $frame2 = $this->imageEncoder->toPaletteGif($this->exportDataMerged($parsedScreen1, $parsedScreen2, $colorTable, true));
            $this->resultMime = 'image/gif';
            return $this->imageEncoder->toAnimatedGif([$frame1, $frame2], [32, 32]);
        }

        $image = $this->exportDataMerged($parsedScreen1, $parsedScreen2, $colorTable, false);
        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
    }

    private function applyInterlace(GdImage $image1, GdImage $image2): void
    {
        if ($this->gigascreenMode === 'interlace1') {
            $this->imageProcessor->interlaceMix($image1, $image2, 1, $this->zoom);
        } elseif ($this->gigascreenMode === 'interlace2') {
            $this->imageProcessor->interlaceMix($image1, $image2, 2, $this->zoom);
        }
    }
}
