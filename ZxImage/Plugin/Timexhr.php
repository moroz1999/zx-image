<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Timexhr\TimexhrAttributeBuilder;
use ZxImage\Plugin\Timexhr\TimexhrPixelRenderer;
use ZxImage\Service\PluginRuntime;

class Timexhr implements PluginInterface
{
    private const int REQUIRED_FILE_SIZE = 12289;

    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->width = 512;
        $this->runtime->height = 384;
    }

    public function convert(): ?string
    {
        $reader = $this->runtime->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            self::REQUIRED_FILE_SIZE,
        );
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

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        $attributes = (new TimexhrAttributeBuilder())->build($attributeByte, $this->runtime->width, $this->runtime->height);
        $pixelsData = (new PixelParser($this->runtime->width))->parse($pixelsArray);
        $parsedScreen = new ParsedScreen($pixelsData, $attributes);

        $paperColor = $parsedScreen->attributes->paperMap[0][0];
        if ($this->runtime->border !== null) {
            $this->runtime->border = $paperColor;
        }

        $image = (new TimexhrPixelRenderer())->render($parsedScreen, $colorTable, $this->runtime->width, $this->runtime->height);
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
