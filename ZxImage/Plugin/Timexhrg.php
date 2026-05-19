<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginRuntime;

class Timexhrg implements PluginInterface
{
    private PluginRuntime $runtime;
    private GigascreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = 12289 * 2;
        $this->runtime->width = 512;
        $this->runtime->height = 384;
        $this->pipeline = new GigascreenPipeline();
    }

    public function convert(): ?string
    {
        return $this->pipeline->convertUsing(
            $this->runtime,
            fn(): ?DualRawScreen => $this->loadBits(),
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderImage(
                $parsedScreen,
                $colorTable,
            ),
            fn(ParsedScreen $first, ParsedScreen $second, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderMergedImage(
                $first,
                $second,
                $colorTable,
            ),
        );
    }

    private function loadBits(): ?DualRawScreen
    {
        $reader = $this->runtime->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            $this->runtime->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $img1 = $reader->readBytes(6144);
        $img2 = $reader->readBytes(6144);
        $attr1 = [$reader->readByte() ?? 0];
        $img3 = $reader->readBytes(6144);
        $img4 = $reader->readBytes(6144);
        $attr2 = [$reader->readByte() ?? 0];

        $pixels1 = [];
        $pixels2 = [];
        for ($i = 0; $i < 6144; $i++) {
            $pixels1[] = $img1[$i];
            $pixels1[] = $img2[$i];
            $pixels2[] = $img3[$i];
            $pixels2[] = $img4[$i];
        }

        return new DualRawScreen(
            new RawScreen($pixels1, $attr1),
            new RawScreen($pixels2, $attr2),
        );
    }

    private function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = $this->buildColorAttributeMap($rawScreen->attributesBytes[0] ?? 0);
        $pixelsData = (new PixelParser($this->runtime->width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes);
    }

    private function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable): GdImage
    {
        $inkColor = $parsedScreen->attributes->inkMap[0][0];
        $paperColor = $parsedScreen->attributes->paperMap[0][0];
        $image = imagecreatetruecolor($this->runtime->width, $this->runtime->height);

        foreach ($parsedScreen->pixelsData as $rowY => $row) {
            $y = $rowY * 2;
            foreach ($row as $x => $pixel) {
                $zxColor = $pixel === 1 ? $inkColor : $paperColor;
                $color = $colorTable->colors[$zxColor];
                imagesetpixel($image, $x, $y, $color);
                imagesetpixel($image, $x, $y + 1, $color);
            }
        }

        $this->runtime->border = $paperColor;
        return $this->pipeline->finalizeImage($image, $colorTable, $this->runtime);
    }

    private function renderMergedImage(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
    ): GdImage {
        $ink1 = $parsedScreen1->attributes->inkMap[0][0];
        $paper1 = $parsedScreen1->attributes->paperMap[0][0];
        $ink2 = $parsedScreen2->attributes->inkMap[0][0];
        $paper2 = $parsedScreen2->attributes->paperMap[0][0];

        $image = imagecreatetruecolor($this->runtime->width, $this->runtime->height);

        foreach ($parsedScreen1->pixelsData as $rowY => $row) {
            $y = $rowY * 2;
            foreach ($row as $x => $pixel1) {
                $pixel2 = $parsedScreen2->pixelsData[$rowY][$x];
                $color1 = $pixel1 === 1 ? $ink1 : $paper1;
                $color2 = $pixel2 === 1 ? $ink2 : $paper2;
                $color = $colorTable->gigaColors[($color1 << 4) | $color2];
                imagesetpixel($image, $x, $y, $color);
                imagesetpixel($image, $x, $y + 1, $color);
            }
        }

        return $this->pipeline->finalizeImage($image, $colorTable, $this->runtime);
    }

    private function buildColorAttributeMap(int $byte): AttributeMap
    {
        $colorCode = ($byte >> 3) & 0x07;
        $colorPairs = [
            [8, 15],
            [9, 14],
            [10, 13],
            [11, 12],
            [12, 11],
            [13, 10],
            [14, 9],
            [15, 8],
        ];
        [$inkKey, $paperKey] = $colorPairs[$colorCode];

        $rows = (int)($this->runtime->height / 8);
        $cols = (int)($this->runtime->width / 8);
        return new AttributeMap(
            array_fill(0, $rows, array_fill(0, $cols, $inkKey)),
            array_fill(0, $rows, array_fill(0, $cols, $paperKey)),
            [],
        );
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
