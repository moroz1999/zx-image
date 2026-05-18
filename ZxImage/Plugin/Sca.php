<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;

class Sca implements PluginInterface
{
    use PluginConfigTrait;

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

        $signature = $reader->readString(3);
        if ($signature !== 'SCA') {
            return null;
        }

        $version = $reader->readByte();
        if ($version !== 1) {
            return null;
        }

        $this->width = $reader->readWord() ?? $this->width;
        $this->height = $reader->readWord() ?? $this->height;
        $this->border = $reader->readByte();
        $framesAmount = $reader->readWord() ?? 0;
        $payloadType = $reader->readByte();
        if ($payloadType !== 0) {
            return null;
        }
        $dataPointer = $reader->readWord() ?? 0;
        $reader->seek($dataPointer);

        $delays = [];
        for ($i = 0; $i < $framesAmount; $i++) {
            $delays[] = (int)(($reader->readByte() ?? 0) * (100 / 50));
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        $gifImages = [];

        for ($i = 0; $i < $framesAmount; $i++) {
            $pixelsBytes = $reader->readBytes(6144);
            $attributesBytes = $reader->readBytes(768);

            $pixelsData = (new PixelParser($this->width))->parse($pixelsBytes);
            $attributes = (new AttributeParser($this->width))->parse($attributesBytes);
            $parsedScreen = new \ZxImage\Dto\ParsedScreen($pixelsData, $attributes);

            $renderer = new PixelRenderer();
            $image = $renderer->render($parsedScreen, false, $colorTable->colors, $this->width, $this->height, $this->attributeWidth, $this->attributeHeight);
            $image = $this->imageProcessor->applyBorder($image, $this->border, $colorTable, $this->width, $this->height, $this->borderWidth, $this->borderHeight, $this->usesBorder);
            $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
            $image = $this->imageProcessor->rotate($image, $this->rotation);

            $gifImages[] = $this->imageEncoder->toPaletteGif($image);
        }

        $this->resultMime = 'image/gif';
        return $this->imageEncoder->toAnimatedGif($gifImages, $delays);
    }
}
