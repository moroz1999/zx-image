<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;

final readonly class StandardScreenPipeline
{
    public function buildFrameSet(PluginRuntime $runtime): ?FrameSet
    {
        return $this->buildFrameSetFor(
            new PluginInput($runtime->sourceFilePath, $runtime->sourceFileContents),
            new PluginGeometry(
                $runtime->width,
                $runtime->height,
                $runtime->attributeWidth,
                $runtime->attributeHeight,
                $runtime->borderWidth,
                $runtime->borderHeight,
                $runtime->usesBorder,
                $runtime->requiredFileSize,
            ),
            $runtime->renderSettings,
            $runtime->services,
        );
    }

    public function buildFrameSetFor(
        PluginInput $input,
        PluginGeometry $geometry,
        RenderSettings $renderSettings,
        PluginServices $services,
    ): ?FrameSet {
        return $this->buildFrameSetUsing(
            null,
            fn(): ?RawScreen => $this->loadBitsFor($input, $geometry, $services),
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen, $geometry->width),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderFrame(
                $parsedScreen,
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
     * @param callable(): ?RawScreen $loadBits
     * @param callable(RawScreen): ParsedScreen $parseScreen
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderFrame
     */
    public function buildFrameSetUsing(
        ?PluginRuntime $runtime,
        callable $loadBits,
        callable $parseScreen,
        callable $renderFrame,
        ?RenderSettings $renderSettings = null,
        ?PluginServices $services = null,
        ?PluginGeometry $geometry = null,
    ): ?FrameSet
    {
        $rawScreen = $loadBits();
        if ($rawScreen === null) {
            return null;
        }

        if ($runtime !== null) {
            $renderSettings ??= $runtime->renderSettings;
            $services ??= $runtime->services;
            $geometry ??= new PluginGeometry(
                $runtime->width,
                $runtime->height,
                $runtime->attributeWidth,
                $runtime->attributeHeight,
                $runtime->borderWidth,
                $runtime->borderHeight,
                $runtime->usesBorder,
                $runtime->requiredFileSize,
            );
        }

        if ($renderSettings === null || $services === null || $geometry === null) {
            return null;
        }

        $colorTable = $services->paletteService->buildColorTable($renderSettings->paletteString);
        $parsedScreen = $parseScreen($rawScreen);
        $hasFlash = count($parsedScreen->attributes->flashMap) > 0;

        if ($hasFlash) {
            return new FrameSet(
                [
                    new Frame($renderFrame($parsedScreen, $colorTable, false), 32),
                    new Frame($renderFrame($parsedScreen, $colorTable, true), 32),
                ],
                $renderSettings,
                $geometry->toRenderGeometry(),
                $colorTable,
            );
        }

        return new FrameSet(
            [new Frame($renderFrame($parsedScreen, $colorTable, false))],
            $renderSettings,
            $geometry->toRenderGeometry(),
            $colorTable,
        );
    }

    public function loadBitsFor(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?RawScreen
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
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

    public function loadBits(PluginRuntime $runtime): ?RawScreen
    {
        $reader = $runtime->services->fileLoader->openSource(
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

    public function renderFrame(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginRuntime|PluginGeometry $runtime,
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
