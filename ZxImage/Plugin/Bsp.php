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

class Bsp implements PluginInterface
{
    use PluginConfigTrait;

    private const int HEADER_SIZE = 70;
    private const int OFFSET_WORD_SIZE = 2;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->borderWidth = 64;
        $this->borderHeight = 64;
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

        $signature = $reader->readString(3);
        if ($signature !== 'bsp') {
            return null;
        }

        $configByte = $reader->readByte();
        if ($configByte === null) {
            return null;
        }

        $hasGigaData = (bool)($configByte & 0b10000000);
        $hasBorderData = (bool)($configByte & 0b01000000);

        $reader->readByte();
        $borderColor = $reader->readByte();
        $reader->readString(32);
        $reader->readString(32);

        $borders = [0, 0];
        if (!$hasBorderData && $borderColor !== null) {
            $borders[0] = $borderColor & 0x07;
            $borders[1] = ($borderColor >> 3) & 0x07;
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        $fileSize = $reader->getSize();

        [$screen1, $screen2] = $this->readScreenData($reader, $hasGigaData, $hasBorderData, $fileSize);

        return $this->buildResult($screen1, $screen2, $hasGigaData, $hasBorderData, $borders, $colorTable);
    }

    private function readScreenData(\ZxImage\Service\BitReader $reader, bool $hasGigaData, bool $hasBorderData, int $fileSize): array
    {
        $secondBorderDataOffset = 0;
        if ($hasBorderData && $hasGigaData) {
            $secondBorderDataOffset = $reader->readWord() ?? 0;
        }

        $pixelsBytes1 = $reader->readBytes(6144);
        $attributesBytes1 = $reader->readBytes(768);

        $pixelsBytes2 = [];
        $attributesBytes2 = [];
        if ($hasGigaData) {
            $pixelsBytes2 = $reader->readBytes(6144);
            $attributesBytes2 = $reader->readBytes(768);
        }

        $borderData1 = [];
        $borderData2 = [];
        if ($hasBorderData) {
            if ($hasGigaData) {
                $firstBorderLength = $secondBorderDataOffset - 6912 * 2 - self::HEADER_SIZE - self::OFFSET_WORD_SIZE;
                $secondBorderLength = $fileSize - $secondBorderDataOffset;
                $borderData1 = $this->parseBorder($reader->readBytes($firstBorderLength));
                $borderData2 = $this->parseBorder($reader->readBytes($secondBorderLength));
            } else {
                $firstBorderLength = $fileSize - 6912 - self::HEADER_SIZE;
                $borderData1 = $this->parseBorder($reader->readBytes($firstBorderLength));
            }
        }

        $attrParser = new AttributeParser($this->width);
        $pixelParser = new PixelParser($this->width);

        $screen1 = new ParsedScreen(
            $pixelParser->parse($pixelsBytes1),
            $attrParser->parse($attributesBytes1),
            [],
            $borderData1,
        );

        if ($hasGigaData) {
            $screen2 = new ParsedScreen(
                $pixelParser->parse($pixelsBytes2),
                $attrParser->parse($attributesBytes2),
                [],
                $borderData2,
            );
        } else {
            $screen2 = $screen1;
        }

        return [$screen1, $screen2];
    }

    private function parseBorder(array $data): array
    {
        $borderHeightBottom = 48;
        $totalWidth = $this->width + $this->borderWidth * 2;
        $totalHeight = $this->height + $this->borderHeight + $borderHeightBottom;
        $borderData = [];
        $x = 0;
        $y = 0;
        $inCenter = false;

        while ($data !== []) {
            $byte = array_shift($data);
            $colorCode = $byte & 0x07;
            $tacts = $byte >> 3;
            $line = 0;
            $untilEnd = false;

            if ($tacts === 0) {
                $untilEnd = true;
            } elseif ($tacts === 1) {
                $line = array_shift($data) ?? 0;
            } elseif ($tacts === 2) {
                $line = 12;
            } else {
                $line = $tacts + 13;
            }
            $line *= 2;

            while ($untilEnd || $line > 0) {
                $borderData[$y][$x] = $colorCode;
                $x++;

                if ($inCenter && $x === $this->borderWidth) {
                    $x = $this->borderWidth + $this->width;
                    $untilEnd = false;
                }
                if ($x === $totalWidth) {
                    $untilEnd = false;
                    $x = 0;
                    $y++;
                    $inCenter = $y >= $this->borderHeight && $y < $totalHeight - $borderHeightBottom;
                }
                if (!$untilEnd) {
                    $line--;
                }
            }
        }

        return $borderData;
    }

    private function buildResult(
        ParsedScreen $screen1,
        ParsedScreen $screen2,
        bool $hasGigaData,
        bool $hasBorderData,
        array $borders,
        ColorTable $colorTable,
    ): string {
        $isFlickerMode = $this->gigascreenMode === 'flicker'
            || $this->gigascreenMode === 'interlace1'
            || $this->gigascreenMode === 'interlace2';

        if ($isFlickerMode) {
            return $this->buildFlickerAnimation($screen1, $screen2, $hasGigaData, $hasBorderData, $borders, $colorTable);
        }

        return $this->buildMixedResult($screen1, $screen2, $hasGigaData, $hasBorderData, $borders, $colorTable);
    }

    private function buildFlickerAnimation(
        ParsedScreen $screen1,
        ParsedScreen $screen2,
        bool $hasGigaData,
        bool $hasBorderData,
        array $borders,
        ColorTable $colorTable,
    ): string {
        $hasFlash = count($screen1->attributes->flashMap) > 0 || count($screen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $image1 = $this->renderSingleImage($screen1, $hasBorderData, $borders[0], $colorTable, false);
            $image2 = $hasGigaData
                ? $this->renderSingleImage($screen2, $hasBorderData, $borders[1], $colorTable, false)
                : $image1;
            $image1f = $this->renderSingleImage($screen1, $hasBorderData, $borders[0], $colorTable, true);
            $image2f = $hasGigaData
                ? $this->renderSingleImage($screen2, $hasBorderData, $borders[1], $colorTable, true)
                : $image1f;

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
            $image1 = $this->renderSingleImage($screen1, $hasBorderData, $borders[0], $colorTable, false);
            $image2 = $hasGigaData
                ? $this->renderSingleImage($screen2, $hasBorderData, $borders[1], $colorTable, false)
                : $image1;

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

    private function buildMixedResult(
        ParsedScreen $screen1,
        ParsedScreen $screen2,
        bool $hasGigaData,
        bool $hasBorderData,
        array $borders,
        ColorTable $colorTable,
    ): string {
        $hasFlash = count($screen1->attributes->flashMap) > 0 || count($screen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $frame1 = $this->imageEncoder->toPaletteGif($this->renderMergedImage($screen1, $screen2, $hasBorderData, $borders, $colorTable, false));
            $frame2 = $this->imageEncoder->toPaletteGif($this->renderMergedImage($screen1, $screen2, $hasBorderData, $borders, $colorTable, true));
            $this->resultMime = 'image/gif';
            return $this->imageEncoder->toAnimatedGif([$frame1, $frame2], [32, 32]);
        }

        $image = $this->renderMergedImage($screen1, $screen2, $hasBorderData, $borders, $colorTable, false);
        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
    }

    private function renderSingleImage(
        ParsedScreen $screen,
        bool $hasBorderData,
        int $borderIndex,
        ColorTable $colorTable,
        bool $flashedImage,
    ): GdImage {
        $image = imagecreatetruecolor($this->width, $this->height);
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

                imagesetpixel($image, $x, $y, $colorTable->colors[$zxColor]);
            }
        }

        $image = $this->applyBspBorder($image, $screen, null, false, $hasBorderData, $borderIndex, $colorTable);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }

    private function renderMergedImage(
        ParsedScreen $screen1,
        ParsedScreen $screen2,
        bool $hasBorderData,
        array $borders,
        ColorTable $colorTable,
        bool $flashedImage,
    ): GdImage {
        $image = imagecreatetruecolor($this->width, $this->height);
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

                imagesetpixel($image, $x, $y, $colorTable->gigaColors[($color1 << 4) | $color2]);
            }
        }

        $image = $this->applyBspBorder($image, $screen1, $screen2, true, $hasBorderData, $borders[0], $colorTable, $borders[1]);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }

    private function applyBspBorder(
        GdImage $centerImage,
        ParsedScreen $screen1,
        ?ParsedScreen $screen2,
        bool $merged,
        bool $hasBorderData,
        int $border1,
        ColorTable $colorTable,
        int $border2 = 0,
    ): GdImage {
        $borderHeightBottom = 48;
        $totalWidth = $this->width + $this->borderWidth * 2;
        $totalHeight = $this->height + $this->borderHeight + $borderHeightBottom;
        $result = imagecreatetruecolor($totalWidth, $totalHeight);

        if ($merged) {
            for ($y = 0; $y < $totalHeight; $y++) {
                for ($x = 0; $x < $totalWidth; $x++) {
                    if ($hasBorderData) {
                        $has1 = isset($screen1->borderData[$y][$x]);
                        $has2 = $screen2 !== null && isset($screen2->borderData[$y][$x]);
                        if ($has1 || $has2) {
                            $c1 = $has1 ? $screen1->borderData[$y][$x] : 0;
                            $c2 = ($has2 && $screen2 !== null) ? $screen2->borderData[$y][$x] : 0;
                            imagesetpixel($result, $x, $y, $colorTable->gigaColors[($c1 << 4) | $c2]);
                        }
                    } else {
                        imagesetpixel($result, $x, $y, $colorTable->gigaColors[($border1 << 4) | $border2]);
                    }
                }
            }
        } else {
            for ($y = 0; $y < $totalHeight; $y++) {
                for ($x = 0; $x < $totalWidth; $x++) {
                    if ($hasBorderData) {
                        if (isset($screen1->borderData[$y][$x])) {
                            imagesetpixel($result, $x, $y, $colorTable->colors[$screen1->borderData[$y][$x]]);
                        } else {
                            imagesetpixel($result, $x, $y, $colorTable->colors[$border1]);
                        }
                    }
                }
            }
        }

        imagecopy($result, $centerImage, $this->borderWidth, $this->borderHeight, 0, 0, $this->width, $this->height);
        return $result;
    }
}
