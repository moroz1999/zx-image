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

class Ulaplus implements PluginInterface
{
    use StandardConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->requiredFileSize = 6976;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    protected function loadBits(): ?RawScreen
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, $this->requiredFileSize);
        if ($reader === null) {
            return null;
        }

        $pixelsBytes = $reader->readBytes(6144);
        $attributesBytes = $reader->readBytes(768);
        $paletteBytes = $reader->readBytes(64);

        return new RawScreen($pixelsBytes, $attributesBytes, $paletteBytes);
    }

    protected function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = $this->parseUlaPlusAttributes($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($this->width))->parse($rawScreen->pixelsBytes);
        $colorOverrides = $this->parseUlaPlusPalette($rawScreen->borderBytes);
        return new ParsedScreen($pixelsData, $attributes, $colorOverrides);
    }

    protected function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $image = imagecreatetruecolor($this->width, $this->height);

        foreach ($parsedScreen->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                $mapX = (int)($x / $this->attributeWidth);
                $mapY = (int)($y / $this->attributeHeight);

                $zxColor = $pixel === 1
                    ? $parsedScreen->attributes->inkMap[$mapY][$mapX]
                    : $parsedScreen->attributes->paperMap[$mapY][$mapX];

                imagesetpixel($image, $x, $y, $parsedScreen->colorOverrides[$zxColor]);
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

    private function parseUlaPlusAttributes(array $bytes): AttributeMap
    {
        $x = 0;
        $y = 0;
        $inkMap = [];
        $paperMap = [];
        $columnsPerRow = (int)($this->width / 8);

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
        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
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
}
