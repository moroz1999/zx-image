<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;
use ZxImage\Service\PluginRuntime;

class Sca implements PluginInterface
{
    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
    }

    public function convert(): ?string
    {
        $reader = $this->runtime->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            null,
        );
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

        $this->runtime->width = $reader->readWord() ?? $this->runtime->width;
        $this->runtime->height = $reader->readWord() ?? $this->runtime->height;
        $this->runtime->border = $reader->readByte();
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

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        $gifImages = [];

        for ($i = 0; $i < $framesAmount; $i++) {
            $pixelsBytes = $reader->readBytes(6144);
            $attributesBytes = $reader->readBytes(768);

            $pixelsData = (new PixelParser($this->runtime->width))->parse($pixelsBytes);
            $attributes = (new AttributeParser($this->runtime->width))->parse($attributesBytes);
            $parsedScreen = new ParsedScreen($pixelsData, $attributes);

            $image = (new PixelRenderer())->render(
                $parsedScreen,
                false,
                $colorTable->colors,
                $this->runtime->width,
                $this->runtime->height,
                $this->runtime->attributeWidth,
                $this->runtime->attributeHeight,
            );
            $image = $this->runtime->imageProcessor->applyBorder(
                $image,
                $this->runtime->border,
                $colorTable,
                $this->runtime->width,
                $this->runtime->height,
                $this->runtime->borderWidth,
                $this->runtime->borderHeight,
                $this->runtime->usesBorder,
            );
            $image = $this->runtime->imageProcessor->resize($image, $this->runtime->zoom, $this->runtime->preFilters, $this->runtime->postFilters);
            $image = $this->runtime->imageProcessor->rotate($image, $this->runtime->rotation);

            $gifImages[] = $this->runtime->imageEncoder->toPaletteGif($image);
        }

        $this->runtime->resultMime = 'image/gif';
        return $this->runtime->imageEncoder->toAnimatedGif($gifImages, $delays);
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
