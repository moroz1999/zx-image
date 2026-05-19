<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;

final readonly class StandardScreenPipeline
{
    public function convert(PluginRuntime $runtime): ?string
    {
        return $this->convertUsing(
            $runtime,
            fn(): ?RawScreen => $this->loadBits($runtime),
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen, $runtime->width),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderImage(
                $parsedScreen,
                $colorTable,
                $flashedImage,
                $runtime,
            ),
        );
    }

    /**
     * @param callable(): ?RawScreen $loadBits
     * @param callable(RawScreen): ParsedScreen $parseScreen
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderImage
     */
    public function convertUsing(
        PluginRuntime $runtime,
        callable $loadBits,
        callable $parseScreen,
        callable $renderImage,
    ): ?string
    {
        $rawScreen = $loadBits();
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $runtime->paletteService->buildColorTable($runtime->paletteString);
        $parsedScreen = $parseScreen($rawScreen);
        $hasFlash = count($parsedScreen->attributes->flashMap) > 0;

        if ($hasFlash) {
            return $this->buildFlashAnimation($parsedScreen, $colorTable, $runtime, $renderImage);
        }

        $image = $renderImage($parsedScreen, $colorTable, false);
        $runtime->resultMime = 'image/png';
        return $runtime->imageEncoder->toPng($image);
    }

    public function loadBits(PluginRuntime $runtime): ?RawScreen
    {
        $reader = $runtime->fileLoader->openSource(
            $runtime->sourceFilePath,
            $runtime->sourceFileContents,
            $runtime->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $pixelsBytes = $reader->readBytes(6144);
        $attributesBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $attributesBytes[] = $byte;
        }

        return new RawScreen($pixelsBytes, $attributesBytes);
    }

    public function parseScreen(RawScreen $rawScreen, int $width): ParsedScreen
    {
        $attributes = (new AttributeParser($width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes);
    }

    public function parseScreenWithLinearPixels(RawScreen $rawScreen, int $width): ParsedScreen
    {
        $linearMapper = static fn(int $y): int => $y;
        $attributes = (new AttributeParser($width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($width))->parse($rawScreen->pixelsBytes, $linearMapper);
        return new ParsedScreen($pixelsData, $attributes);
    }

    public function parseScreenWithZxAttributes(RawScreen $rawScreen, int $width): ParsedScreen
    {
        $zxyMapper = \Closure::fromCallable([$this, 'calculateZxY']);
        $attributes = (new AttributeParser($width))->parse($rawScreen->attributesBytes, $zxyMapper);
        $pixelsData = (new PixelParser($width))->parse($rawScreen->pixelsBytes);
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

    private function buildFlashAnimation(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        PluginRuntime $runtime,
        callable $renderImage,
    ): string {
        $frame1 = $runtime->imageEncoder->toPaletteGif($renderImage($parsedScreen, $colorTable, false));
        $frame2 = $runtime->imageEncoder->toPaletteGif($renderImage($parsedScreen, $colorTable, true));
        $runtime->resultMime = 'image/gif';
        return $runtime->imageEncoder->toAnimatedGif([$frame1, $frame2], [32, 32]);
    }

    private function calculateZxY(int $y): int
    {
        $offset = 0;
        if ($y > 127) {
            $offset = 128;
            $y -= 128;
        } elseif ($y > 63) {
            $offset = 64;
            $y -= 64;
        }

        $rows = (int)($y / 8);
        $rests = $y - $rows * 8;
        return $offset + $rests * 8 + $rows;
    }
}
