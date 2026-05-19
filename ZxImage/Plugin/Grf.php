<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Plugin\Grf\GrfAspectScaler;
use ZxImage\Plugin\Grf\GrfPaletteParser;
use ZxImage\Plugin\Grf\GrfPixelParser;
use ZxImage\Service\PixelCanvas;
use ZxImage\Service\PluginRuntime;

class Grf implements PluginInterface
{
    private const int PROFI_COLOR_FORMAT = 19;

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

        $this->runtime->width = $reader->readWord() ?? $this->runtime->width;
        $this->runtime->height = $reader->readWord() ?? $this->runtime->height;
        $bpp = $reader->readByte() ?? 4;
        $reader->readByte(); // amod
        $reader->readByte(); // bps lo
        $reader->readByte(); // bps hi
        $reader->readByte(); // hlen
        $format = $reader->readByte() ?? 0;

        $paletteBytes = [];
        if ($format === self::PROFI_COLOR_FORMAT) {
            $paletteBytes = $reader->readBytes(16);
            $reader->readBytes(102);
        } else {
            $reader->readBytes(118);
        }

        $pixelsArray = [];
        $attributesArray = [];
        $length = (int)($this->runtime->width * $this->runtime->height / $bpp);
        do {
            $pixelsArray[] = $reader->readByte();
            $attributesArray[] = $reader->readByte();
        } while ($length = $length - 2);

        $pixelsData = (new GrfPixelParser())->parse($pixelsArray, $attributesArray, $this->runtime->width);
        $colors = (new GrfPaletteParser())->parse($paletteBytes);

        $image = (new PixelCanvas())->draw($pixelsData, $colors, $this->runtime->width, $this->runtime->height);
        $image = (new GrfAspectScaler())->scale($image);

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
