<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Ulaplus implements PluginInterface
{
    private PluginRuntime $runtime;
    private StandardScreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = 6976;
        $this->pipeline = new StandardScreenPipeline();
    }

    public function convert(): ?string
    {
        return $this->pipeline->convertUsing(
            $this->runtime,
            fn(): ?RawScreen => $this->loadBits(),
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderImage(
                $parsedScreen,
                $colorTable,
            ),
        );
    }

    private function loadBits(): ?RawScreen
    {
        $reader = $this->runtime->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            $this->runtime->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $pixelsBytes = $reader->readBytes(6144);
        $attributesBytes = $reader->readBytes(768);
        $paletteBytes = $reader->readBytes(64);

        return new RawScreen($pixelsBytes, $attributesBytes, $paletteBytes);
    }

    private function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = $this->parseUlaPlusAttributes($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($this->runtime->width))->parse($rawScreen->pixelsBytes);
        $colorOverrides = $this->parseUlaPlusPalette($rawScreen->borderBytes);
        return new ParsedScreen($pixelsData, $attributes, $colorOverrides);
    }

    private function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable): GdImage
    {
        $image = imagecreatetruecolor($this->runtime->width, $this->runtime->height);

        foreach ($parsedScreen->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                $mapX = (int)($x / $this->runtime->attributeWidth);
                $mapY = (int)($y / $this->runtime->attributeHeight);

                $zxColor = $pixel === 1
                    ? $parsedScreen->attributes->inkMap[$mapY][$mapX]
                    : $parsedScreen->attributes->paperMap[$mapY][$mapX];

                imagesetpixel($image, $x, $y, $parsedScreen->colorOverrides[$zxColor]);
            }
        }

        return $this->pipeline->finalizeImage($image, $colorTable, $this->runtime);
    }

    private function parseUlaPlusAttributes(array $bytes): AttributeMap
    {
        $x = 0;
        $y = 0;
        $inkMap = [];
        $paperMap = [];
        $columnsPerRow = (int)($this->runtime->width / 8);

        foreach ($bytes as $byte) {
            $group = ($byte >> 6) & 0x03;
            $inkMap[$y][$x] = $group * 16 + ($byte & 0x07);
            $paperMap[$y][$x] = $group * 16 + (($byte >> 3) & 0x07) + 8;

            if ($x === $columnsPerRow - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }

        return new AttributeMap($inkMap, $paperMap, []);
    }

    private function parseUlaPlusPalette(array $bytes): array
    {
        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        $config = $colorTable->config;
        $paletteData = [];

        foreach ($bytes as $byte) {
            $g = ($byte >> 5) & 0x07;
            $r = ($byte >> 2) & 0x07;
            $b = $byte & 0x03;

            $rValue = $r * 32;
            $gValue = $g * 32;
            $bValue = $b * 64;

            $redChannel = (int)round(
                ($rValue * $config->r11 + $gValue * $config->r12 + $bValue * $config->r13) / 0xFF
            );
            $greenChannel = (int)round(
                ($rValue * $config->r21 + $gValue * $config->r22 + $bValue * $config->r23) / 0xFF
            );
            $blueChannel = (int)round(
                ($rValue * $config->r31 + $gValue * $config->r32 + $bValue * $config->r33) / 0xFF
            );

            $paletteData[] = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }
        return $paletteData;
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
