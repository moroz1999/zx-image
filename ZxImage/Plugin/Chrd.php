<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\AttributeParser;

class Chrd implements PluginInterface
{
    use PluginConfigTrait;

    private const int COLOR_TYPE_STANDARD = 9;
    private const int COLOR_TYPE_GIGASCREEN = 18;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->usesBorder = false;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    public function convert(): ?string
    {
        $chrdData = $this->loadChrdData();
        if ($chrdData === null) {
            return null;
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);

        if ($chrdData['colorType'] === self::COLOR_TYPE_STANDARD) {
            return $this->renderStandard($chrdData['screen1'], $colorTable);
        }

        if ($chrdData['colorType'] === self::COLOR_TYPE_GIGASCREEN) {
            return $this->renderGigascreen($chrdData['screen1'], $chrdData['screen2'], $colorTable);
        }

        return null;
    }

    /**
     * @return array{colorType: int, screen1: ParsedScreen, screen2: ParsedScreen}|null
     */
    private function loadChrdData(): ?array
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, null);
        if ($reader === null) {
            return null;
        }

        $signature = $reader->readString(4);
        if ($signature === null || strtolower($signature) !== 'chr$') {
            return null;
        }

        $widthInChars = $reader->readByte();
        $heightInChars = $reader->readByte();
        $colorType = $reader->readByte();

        if ($widthInChars === null || $heightInChars === null || $colorType === null) {
            return null;
        }

        $this->width = $widthInChars * 8;
        $this->height = $heightInChars * 8;

        $attributesArray1 = [];
        $attributesArray2 = [];

        for ($charY = 0; $charY < $heightInChars; $charY++) {
            for ($charX = 0; $charX < $widthInChars; $charX++) {
                if ($colorType === self::COLOR_TYPE_STANDARD) {
                    for ($i = 0; $i < 8; $i++) {
                        $reader->readByte();
                    }
                    $attributesArray1[] = $reader->readByte() ?? 0;
                } elseif ($colorType === self::COLOR_TYPE_GIGASCREEN) {
                    for ($i = 0; $i < 8; $i++) {
                        $reader->readByte();
                    }
                    $attributesArray1[] = $reader->readByte() ?? 0;

                    for ($i = 0; $i < 8; $i++) {
                        $reader->readByte();
                    }
                    $attributesArray2[] = $reader->readByte() ?? 0;
                }
            }
        }

        $zeroPixels = $this->buildZeroPixels();
        $attrParser = new AttributeParser($this->width);
        $attributes1 = $attrParser->parse($attributesArray1);
        $screen1 = new ParsedScreen($zeroPixels, $attributes1);

        $attributes2 = new AttributeMap([], [], []);
        if ($colorType === self::COLOR_TYPE_GIGASCREEN) {
            $attributes2 = $attrParser->parse($attributesArray2);
        }
        $screen2 = new ParsedScreen($zeroPixels, $attributes2);

        return [
            'colorType' => $colorType,
            'screen1' => $screen1,
            'screen2' => $screen2,
        ];
    }

    private function buildZeroPixels(): array
    {
        $pixelsData = [];
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                $pixelsData[$y][$x] = 0;
            }
        }
        return $pixelsData;
    }

    private function renderStandard(ParsedScreen $screen, ColorTable $colorTable): string
    {
        $hasFlash = count($screen->attributes->flashMap) > 0;

        if ($hasFlash) {
            $frame1 = $this->imageEncoder->toPaletteGif($this->renderSingleImage($screen, $colorTable, false));
            $frame2 = $this->imageEncoder->toPaletteGif($this->renderSingleImage($screen, $colorTable, true));
            $this->resultMime = 'image/gif';
            return $this->imageEncoder->toAnimatedGif([$frame1, $frame2], [32, 32]);
        }

        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($this->renderSingleImage($screen, $colorTable, false));
    }

    private function renderSingleImage(ParsedScreen $screen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
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
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }

    private function renderGigascreen(ParsedScreen $screen1, ParsedScreen $screen2, ColorTable $colorTable): string
    {
        $isFlickerMode = $this->gigascreenMode === 'flicker'
            || $this->gigascreenMode === 'interlace1'
            || $this->gigascreenMode === 'interlace2';

        if ($isFlickerMode) {
            return $this->buildFlickerAnimation($screen1, $screen2, $colorTable);
        }

        return $this->buildMixedResult($screen1, $screen2, $colorTable);
    }

    private function buildFlickerAnimation(ParsedScreen $screen1, ParsedScreen $screen2, ColorTable $colorTable): string
    {
        $hasFlash = count($screen1->attributes->flashMap) > 0 || count($screen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $image1 = $this->renderSingleImage($screen1, $colorTable, false);
            $image2 = $this->renderSingleImage($screen2, $colorTable, false);
            $image1f = $this->renderSingleImage($screen1, $colorTable, true);
            $image2f = $this->renderSingleImage($screen2, $colorTable, true);

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
            $image1 = $this->renderSingleImage($screen1, $colorTable, false);
            $image2 = $this->renderSingleImage($screen2, $colorTable, false);

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

    private function buildMixedResult(ParsedScreen $screen1, ParsedScreen $screen2, ColorTable $colorTable): string
    {
        $hasFlash = count($screen1->attributes->flashMap) > 0 || count($screen2->attributes->flashMap) > 0;

        if ($hasFlash) {
            $frame1 = $this->imageEncoder->toPaletteGif($this->renderMergedImage($screen1, $screen2, $colorTable, false));
            $frame2 = $this->imageEncoder->toPaletteGif($this->renderMergedImage($screen1, $screen2, $colorTable, true));
            $this->resultMime = 'image/gif';
            return $this->imageEncoder->toAnimatedGif([$frame1, $frame2], [32, 32]);
        }

        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($this->renderMergedImage($screen1, $screen2, $colorTable, false));
    }

    private function renderMergedImage(ParsedScreen $screen1, ParsedScreen $screen2, ColorTable $colorTable, bool $flashedImage): GdImage
    {
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
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }
}
