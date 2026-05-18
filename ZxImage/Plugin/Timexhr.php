<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\PixelParser;

class Timexhr implements PluginInterface
{
    use PluginConfigTrait;

    private const int REQUIRED_FILE_SIZE = 12289;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->width = 512;
        $this->height = 384;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    public function convert(): ?string
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, self::REQUIRED_FILE_SIZE);
        if ($reader === null) {
            return null;
        }

        $pixelsArray1 = $reader->readBytes(6144);
        $pixelsArray2 = $reader->readBytes(6144);
        $attributeByte = $reader->readByte() ?? 0;

        $pixelsArray = [];
        for ($i = 0; $i < 6144; $i++) {
            $pixelsArray[] = $pixelsArray1[$i];
            $pixelsArray[] = $pixelsArray2[$i];
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        $attributes = $this->buildColorAttributeMap($attributeByte);
        $pixelsData = (new PixelParser($this->width))->parse($pixelsArray);
        $parsedScreen = new ParsedScreen($pixelsData, $attributes);

        $image = $this->renderImage($parsedScreen, $colorTable);
        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
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
        $inkMap = array_fill(0, $rows, array_fill(0, $cols, $inkKey));
        $paperMap = array_fill(0, $rows, array_fill(0, $cols, $paperKey));
        return new AttributeMap($inkMap, $paperMap, []);
    }

    private function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable): GdImage
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

        if ($this->border) {
            $this->border = $paperColor;
        }
        $image = $this->imageProcessor->applyBorder($image, $this->border, $colorTable, $this->width, $this->height, $this->borderWidth, $this->borderHeight, $this->usesBorder);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }
}
