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

class Timexhrg implements PluginInterface
{
    use GigascreenConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->requiredFileSize = 12289 * 2;
        $this->width = 512;
        $this->height = 384;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    protected function loadBits(): ?DualRawScreen
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, $this->requiredFileSize);
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

    protected function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = $this->buildColorAttributeMap($rawScreen->attributesBytes[0] ?? 0);
        $pixelsData = (new PixelParser($this->width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes);
    }

    protected function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $inkColor = $parsedScreen->attributes->inkMap[0][0];
        $paperColor = $parsedScreen->attributes->paperMap[0][0];
        $image = imagecreatetruecolor($this->width, $this->height);

        foreach ($parsedScreen->pixelsData as $rowY => $row) {
            $y = $rowY * 2;
            foreach ($row as $x => $pixel) {
                $zxColor = $pixel === 1 ? $inkColor : $paperColor;
                $color = $colorTable->colors[$zxColor];
                imagesetpixel($image, $x, $y, $color);
                imagesetpixel($image, $x, $y + 1, $color);
            }
        }

        $this->border = $paperColor;
        $image = $this->imageProcessor->applyBorder($image, $this->border, $colorTable, $this->width, $this->height, $this->borderWidth, $this->borderHeight, $this->usesBorder);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }

    protected function exportDataMerged(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        bool $flashedImage,
    ): GdImage {
        $ink1 = $parsedScreen1->attributes->inkMap[0][0];
        $paper1 = $parsedScreen1->attributes->paperMap[0][0];
        $ink2 = $parsedScreen2->attributes->inkMap[0][0];
        $paper2 = $parsedScreen2->attributes->paperMap[0][0];

        $image = imagecreatetruecolor($this->width, $this->height);

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

        $image = $this->imageProcessor->applyBorder($image, $this->border, $colorTable, $this->width, $this->height, $this->borderWidth, $this->borderHeight, $this->usesBorder);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
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

        $rows = (int)($this->height / 8);
        $cols = (int)($this->width / 8);
        return new AttributeMap(
            array_fill(0, $rows, array_fill(0, $cols, $inkKey)),
            array_fill(0, $rows, array_fill(0, $cols, $paperKey)),
            [],
        );
    }
}
