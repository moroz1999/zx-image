<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\MghBorders;
use ZxImage\Dto\MghDimensions;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Multiartist\MghAttributeParser;
use ZxImage\Plugin\Multiartist\MghBorderRenderer;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginRuntime;

class Multiartist implements PluginInterface
{
    private const int MGH_MODE_1 = 1;
    private const int MGH_MODE_2 = 2;
    private const int MGH_MODE_4 = 4;
    private const int MGH_MODE_8 = 8;

    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
    }

    public function convert(): ?string
    {
        $reader = $this->runtime->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            null,
        );
        if ($reader === null) {
            return null;
        }

        $header = $reader->readString(256);
        if ($header === null) {
            return null;
        }

        $signature = substr($header, 0, 3);
        $version = ord(substr($header, 3, 1));
        if ($signature !== 'MGH' || $version !== 1) {
            return null;
        }

        $mghMode = ord(substr($header, 4, 1));
        $borders = $this->parseBorders($header);

        $dimensions = $this->getMghDimensions($mghMode);
        $this->runtime->attributeHeight = $dimensions->attributeHeight;

        $pixelsBytes1 = $reader->readBytes(6144);
        $pixelsBytes2 = $reader->readBytes(6144);
        $attributesBytes1 = $reader->readBytes($dimensions->attributesLength);
        $attributesBytes2 = $reader->readBytes($dimensions->attributesLength);

        $outerAttributesBytes1 = [];
        $outerAttributesBytes2 = [];
        if ($mghMode === self::MGH_MODE_1) {
            $outerAttributesBytes1 = $reader->readBytes($dimensions->outerAttributesLength);
            $outerAttributesBytes2 = $reader->readBytes($dimensions->outerAttributesLength);
        }

        $attrParser = new MghAttributeParser();
        $pixelParser = new PixelParser($this->runtime->width);
        $screen1 = new ParsedScreen(
            $pixelParser->parse($pixelsBytes1),
            $attrParser->parse($mghMode, $attributesBytes1, $outerAttributesBytes1, $this->runtime->width),
        );
        $screen2 = new ParsedScreen(
            $pixelParser->parse($pixelsBytes2),
            $attrParser->parse($mghMode, $attributesBytes2, $outerAttributesBytes2, $this->runtime->width),
        );

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        return $this->buildResult($screen1, $screen2, $borders, $colorTable);
    }

    private function parseBorders(string $header): MghBorders
    {
        if ($this->runtime->border !== null) {
            return new MghBorders(ord(substr($header, 5, 1)), ord(substr($header, 6, 1)));
        }
        return new MghBorders(null, null);
    }

    private function getMghDimensions(int $mghMode): MghDimensions
    {
        return match ($mghMode) {
            self::MGH_MODE_1 => new MghDimensions(1, 3072, 384),
            self::MGH_MODE_2 => new MghDimensions(2, 3072, 0),
            self::MGH_MODE_4 => new MghDimensions(4, 1536, 0),
            default => new MghDimensions(8, 768, 0),
        };
    }

    private function buildResult(ParsedScreen $screen1, ParsedScreen $screen2, MghBorders $borders, ColorTable $colorTable): string
    {
        $pipeline = new GigascreenPipeline();
        $borderRenderer = new MghBorderRenderer();

        $renderSingle1 = function (ParsedScreen $screen, ColorTable $ct, bool $flashedImage) use ($borders, $screen1): GdImage {
            $borderIndex = $screen === $screen1 ? $borders->border1 : $borders->border2;
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
            $image = $this->runtime->imageProcessor->applyBorder(
                $center, $borderIndex, $ct,
                $this->runtime->width, $this->runtime->height,
                $this->runtime->borderWidth, $this->runtime->borderHeight, $this->runtime->usesBorder,
            );
            $image = $this->runtime->imageProcessor->resize($image, $this->runtime->zoom, $this->runtime->preFilters, $this->runtime->postFilters);
            return $this->runtime->imageProcessor->rotate($image, $this->runtime->rotation);
        };

        $renderMerged = function (ParsedScreen $s1, ParsedScreen $s2, ColorTable $ct, bool $flashedImage) use ($borders, $borderRenderer): GdImage {
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
            $image = $borderRenderer->apply($center, $borders->border1, $borders->border2, $ct, $this->runtime);
            $image = $this->runtime->imageProcessor->resize($image, $this->runtime->zoom, $this->runtime->preFilters, $this->runtime->postFilters);
            return $this->runtime->imageProcessor->rotate($image, $this->runtime->rotation);
        };

        return $pipeline->buildFromParsedScreens($screen1, $screen2, $colorTable, $this->runtime, $renderSingle1, $renderMerged);
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
