<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Plugin\Atmega\AtmegaPaletteParser;
use ZxImage\Plugin\Atmega\AtmegaPixelParser;
use ZxImage\Service\PixelCanvas;
use ZxImage\Service\PluginRuntime;

class Atmega implements PluginInterface
{
    private const int PIXEL_PAGE_SIZE = 8000;
    private const int FILE_SIZE_WITH_GAPS = 32896;

    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->width = 320;
        $this->runtime->height = 200;
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

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        $fileSize = $reader->getSize();

        $pixelsArray = [];
        if ($fileSize === self::FILE_SIZE_WITH_GAPS) {
            for ($page = 0; $page < 4; $page++) {
                $pixelsArray = array_merge($pixelsArray, $reader->readBytes(self::PIXEL_PAGE_SIZE));
                $reader->readBytes(192);
            }
        } else {
            $pixelsArray = $reader->readBytes(self::PIXEL_PAGE_SIZE * 4);
        }
        $reader->readBytes(21);

        $paletteBytes = [
            0b00000000,
            0b00000001,
            0b00000010,
            0b00000011,
            0b00010000,
            0b00010001,
            0b00010010,
            0b00010011,
            0b00000000,
            0b00100001,
            0b01000010,
            0b01100011,
            0b10010000,
            0b10110001,
            0b11010010,
            0b11110011,
        ];

        $colors = (new AtmegaPaletteParser())->parse($paletteBytes, $colorTable->config);
        $pixelsData = (new AtmegaPixelParser())->parse($pixelsArray, $this->runtime->width);

        $image = (new PixelCanvas())->draw($pixelsData, $colors, $this->runtime->width, $this->runtime->height);

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

        $this->runtime->resultMime = 'image/png';
        return $this->runtime->imageEncoder->toPng($image);
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