<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;

class Bsc implements PluginInterface
{
    use StandardConvertTrait;

    private const int ATTRIBUTES_LENGTH = 768;
    private const int FILE_SIZE = 11136;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->borderWidth = 64;
        $this->borderHeight = 56;
        $this->requiredFileSize = self::FILE_SIZE;
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
        $attributesBytes = $reader->readBytes(self::ATTRIBUTES_LENGTH);
        $borderBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $borderBytes[] = $byte;
        }
        return new RawScreen($pixelsBytes, $attributesBytes, $borderBytes);
    }

    protected function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = (new AttributeParser($this->width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($this->width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes, [], $rawScreen->borderBytes);
    }

    protected function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $renderer = new \ZxImage\Plugin\Standard\PixelRenderer();
        $image = $renderer->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $this->width,
            $this->height,
            $this->attributeWidth,
            $this->attributeHeight,
        );

        $image = $this->applyBscBorder($image, $parsedScreen, $colorTable);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }

    private function applyBscBorder(GdImage $centerImage, ParsedScreen $parsedScreen, ColorTable $colorTable): GdImage
    {
        if ($this->border === null) {
            return $centerImage;
        }

        $resultImage = imagecreatetruecolor(
            $this->width + $this->borderWidth * 2,
            $this->height + $this->borderHeight * 2,
        );

        $x = 0;
        $y = 0;

        foreach ($parsedScreen->borderData as $byte) {
            $leftColor = $byte & 0x07;
            $color = $colorTable->colors[$leftColor];
            for ($i = 0; $i < 8; $i++) {
                imagesetpixel($resultImage, $x + $i, $y, $color);
            }

            $x += 8;
            $rightColor = ($byte >> 3) & 0x07;
            $color = $colorTable->colors[$rightColor];
            for ($i = 0; $i < 8; $i++) {
                imagesetpixel($resultImage, $x + $i, $y, $color);
            }

            $x += 8;
            if ($y >= ($this->borderHeight + 8) && $y < ($this->height + $this->borderHeight + 8) && $x === $this->borderWidth) {
                $x += $this->width;
            }

            if ($x >= $this->width + $this->borderWidth * 2) {
                $x = 0;
                $y++;
            }
        }

        imagecopy($resultImage, $centerImage, $this->borderWidth, $this->borderHeight + 8, 0, 0, $this->width, $this->height);
        return $resultImage;
    }
}
