<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;

class Multiartist implements PluginInterface
{
    use PluginConfigTrait;

    private const int MGH_MODE_1 = 1;
    private const int MGH_MODE_2 = 2;
    private const int MGH_MODE_4 = 4;
    private const int MGH_MODE_8 = 8;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    public function convert(): ?string
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, null);
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

        [$attributeHeight, $attributesLength, $outerAttributesLength] = $this->getMghDimensions($mghMode);
        $this->attributeHeight = $attributeHeight;

        $pixelsBytes1 = $reader->readBytes(6144);
        $pixelsBytes2 = $reader->readBytes(6144);
        $attributesBytes1 = $reader->readBytes($attributesLength);
        $attributesBytes2 = $reader->readBytes($attributesLength);

        $outerAttributesBytes1 = [];
        $outerAttributesBytes2 = [];
        if ($mghMode === self::MGH_MODE_1) {
            $outerAttributesBytes1 = $reader->readBytes($outerAttributesLength);
            $outerAttributesBytes2 = $reader->readBytes($outerAttributesLength);
        }

        $pixelParser = new PixelParser($this->width);
        $screen1 = new ParsedScreen(
            $pixelParser->parse($pixelsBytes1),
            $this->buildAttributeMap($mghMode, $attributesBytes1, $outerAttributesBytes1),
        );
        $screen2 = new ParsedScreen(
            $pixelParser->parse($pixelsBytes2),
            $this->buildAttributeMap($mghMode, $attributesBytes2, $outerAttributesBytes2),
        );

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        return $this->buildResult($screen1, $screen2, $borders, $colorTable);
    }

    private function parseBorders(string $header): array
    {
        if (is_numeric($this->border)) {
            return [ord(substr($header, 5, 1)), ord(substr($header, 6, 1))];
        }
        return [false, false];
    }

    private function getMghDimensions(int $mghMode): array
    {
        return match ($mghMode) {
            self::MGH_MODE_1 => [1, 3072, 384],
            self::MGH_MODE_2 => [2, 3072, 0],
            self::MGH_MODE_4 => [4, 1536, 0],
            default => [8, 768, 0],
        };
    }

    private function buildAttributeMap(int $mghMode, array $innerBytes, array $outerBytes): AttributeMap
    {
        if ($mghMode === self::MGH_MODE_1) {
            return $this->buildMgh1AttributeMap($innerBytes, $outerBytes);
        }
        return (new AttributeParser($this->width))->parse($innerBytes);
    }

    private function buildMgh1AttributeMap(array $innerBytes, array $outerBytes): AttributeMap
    {
        $x = 8;
        $y = 0;
        $inkMap = [];
        $paperMap = [];
        $flashMap = [];

        foreach ($innerBytes as $byte) {
            $bright = ($byte >> 6) & 1;
            $inkMap[$y][$x] = ($bright << 3) | ($byte & 0x07);
            $paperMap[$y][$x] = ($bright << 3) | (($byte >> 3) & 0x07);
            if (($byte >> 7) & 1) {
                $flashMap[$y][$x] = true;
            }
            if ($x === 23) {
                $x = 8;
                $y++;
            } else {
                $x++;
            }
        }

        $x = 0;
        $y = 0;
        foreach ($outerBytes as $byte) {
            $bright = ($byte >> 6) & 1;
            $inkKey = ($bright << 3) | ($byte & 0x07);
            $paperKey = ($bright << 3) | (($byte >> 3) & 0x07);
            $isFlash = ($byte >> 7) & 1;

            for ($i = 0; $i < 8; $i++) {
                $inkMap[$y + $i][$x] = $inkKey;
                $paperMap[$y + $i][$x] = $paperKey;
                if ($isFlash) {
                    $flashMap[$y + $i][$x] = true;
                }
            }

            if ($x === 7) {
                $x = 24;
            } elseif ($x === 31) {
                $x = 0;
                $y += 8;
            } else {
                $x++;
            }
        }

        return new AttributeMap($inkMap, $paperMap, $flashMap);
    }

    private function buildResult(ParsedScreen $screen1, ParsedScreen $screen2, array $borders, ColorTable $colorTable): string
    {
        $isFlickerMode = $this->gigascreenMode === 'flicker'
            || $this->gigascreenMode === 'interlace1'
            || $this->gigascreenMode === 'interlace2';

        if ($isFlickerMode) {
            return $this->buildFlickerAnimation($screen1, $screen2, $borders, $colorTable);
        }

        return $this->buildMixedResult($screen1, $screen2, $borders, $colorTable);
    }

    private function buildFlickerAnimation(ParsedScreen $screen1, ParsedScreen $screen2, array $borders, ColorTable $colorTable): string
    {
        $hasFlash = count($screen1->attributes->flashMap) > 0 || count($screen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $image1 = $this->renderWithBorder($screen1, $borders[0], $colorTable, false);
            $image2 = $this->renderWithBorder($screen2, $borders[1], $colorTable, false);
            $image1f = $this->renderWithBorder($screen1, $borders[0], $colorTable, true);
            $image2f = $this->renderWithBorder($screen2, $borders[1], $colorTable, true);

            if ($this->gigascreenMode === 'interlace1') {
                $this->imageProcessor->interlaceMix($image1, $image2, 1, $this->zoom);
                $this->imageProcessor->interlaceMix($image1f, $image2f, 1, $this->zoom);
            } elseif ($this->gigascreenMode === 'interlace2') {
                $this->imageProcessor->interlaceMix($image1, $image2, 2, $this->zoom);
                $this->imageProcessor->interlaceMix($image1f, $image2f, 2, $this->zoom);
            }

            $frame1 = $this->imageEncoder->toPaletteGif($image1);
            $frame2 = $this->imageEncoder->toPaletteGif($image2);
            $frame1f = $this->imageEncoder->toPaletteGif($image1f);
            $frame2f = $this->imageEncoder->toPaletteGif($image2f);

            $gifImages = [];
            $delays = [];
            for ($i = 0; $i < 32; $i++) {
                $gifImages[] = $i < 16 ? (($i & 1) ? $frame1 : $frame2) : (($i & 1) ? $frame1f : $frame2f);
                $delays[] = 2;
            }
        } else {
            $image1 = $this->renderWithBorder($screen1, $borders[0], $colorTable, false);
            $image2 = $this->renderWithBorder($screen2, $borders[1], $colorTable, false);

            if ($this->gigascreenMode === 'interlace1') {
                $this->imageProcessor->interlaceMix($image1, $image2, 1, $this->zoom);
            } elseif ($this->gigascreenMode === 'interlace2') {
                $this->imageProcessor->interlaceMix($image1, $image2, 2, $this->zoom);
            }

            $gifImages = [
                $this->imageEncoder->toPaletteGif($image1),
                $this->imageEncoder->toPaletteGif($image2),
            ];
            $delays = [2, 2];
        }

        $this->resultMime = 'image/gif';
        return $this->imageEncoder->toAnimatedGif($gifImages, $delays);
    }

    private function buildMixedResult(ParsedScreen $screen1, ParsedScreen $screen2, array $borders, ColorTable $colorTable): string
    {
        $hasFlash = count($screen1->attributes->flashMap) > 0 || count($screen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $frame1 = $this->imageEncoder->toPaletteGif($this->renderMergedWithBorder($screen1, $screen2, $borders, $colorTable, false));
            $frame2 = $this->imageEncoder->toPaletteGif($this->renderMergedWithBorder($screen1, $screen2, $borders, $colorTable, true));
            $this->resultMime = 'image/gif';
            return $this->imageEncoder->toAnimatedGif([$frame1, $frame2], [32, 32]);
        }

        $image = $this->renderMergedWithBorder($screen1, $screen2, $borders, $colorTable, false);
        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
    }

    private function renderWithBorder(ParsedScreen $screen, mixed $borderIndex, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $center = imagecreatetruecolor($this->width, $this->height);
        foreach ($screen->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                $mapX = (int)($x / $this->attributeWidth);
                $mapY = (int)($y / $this->attributeHeight);

                if ($flashedImage && isset($screen->attributes->flashMap[$mapY][$mapX])) {
                    $zxColor = $pixel === 1
                        ? $screen->attributes->paperMap[$mapY][$mapX]
                        : $screen->attributes->inkMap[$mapY][$mapX];
                } else {
                    $zxColor = $pixel === 1
                        ? $screen->attributes->inkMap[$mapY][$mapX]
                        : $screen->attributes->paperMap[$mapY][$mapX];
                }

                imagesetpixel($center, $x, $y, $colorTable->colors[$zxColor]);
            }
        }

        $image = $this->imageProcessor->applyBorder(
            $center,
            is_numeric($borderIndex) ? (int)$borderIndex : null,
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

    private function renderMergedWithBorder(ParsedScreen $screen1, ParsedScreen $screen2, array $borders, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $center = imagecreatetruecolor($this->width, $this->height);
        foreach ($screen1->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel1) {
                $mapX = (int)($x / $this->attributeWidth);
                $mapY = (int)($y / $this->attributeHeight);
                $pixel2 = $screen2->pixelsData[$y][$x];

                if ($flashedImage && isset($screen1->attributes->flashMap[$mapY][$mapX])) {
                    $color1 = $pixel1 === 1
                        ? $screen1->attributes->paperMap[$mapY][$mapX]
                        : $screen1->attributes->inkMap[$mapY][$mapX];
                } else {
                    $color1 = $pixel1 === 1
                        ? $screen1->attributes->inkMap[$mapY][$mapX]
                        : $screen1->attributes->paperMap[$mapY][$mapX];
                }

                if ($flashedImage && isset($screen2->attributes->flashMap[$mapY][$mapX])) {
                    $color2 = $pixel2 === 1
                        ? $screen2->attributes->paperMap[$mapY][$mapX]
                        : $screen2->attributes->inkMap[$mapY][$mapX];
                } else {
                    $color2 = $pixel2 === 1
                        ? $screen2->attributes->inkMap[$mapY][$mapX]
                        : $screen2->attributes->paperMap[$mapY][$mapX];
                }

                imagesetpixel($center, $x, $y, $colorTable->gigaColors[($color1 << 4) | $color2]);
            }
        }

        $image = $this->applyMghBorder($center, $borders, $colorTable);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }

    private function applyMghBorder(GdImage $center, array $borders, ColorTable $colorTable): GdImage
    {
        if (is_numeric($borders[0]) && is_numeric($borders[1])) {
            $result = imagecreatetruecolor(320, 240);
            $color = $colorTable->gigaColors[((int)$borders[0] << 4) | (int)$borders[1]];
            imagefill($result, 0, 0, $color);
            imagecopy($result, $center, 32, 24, 0, 0, $this->width, $this->height);
            return $result;
        }

        return $this->imageProcessor->applyBorder(
            $center,
            is_numeric($borders[0]) ? (int)$borders[0] : null,
            $colorTable,
            $this->width,
            $this->height,
            $this->borderWidth,
            $this->borderHeight,
            $this->usesBorder,
        );
    }
}
