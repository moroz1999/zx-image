<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Ulaplus\UlaplusAttributeParser;
use ZxImage\Plugin\Ulaplus\UlaplusLoader;
use ZxImage\Plugin\Ulaplus\UlaplusPaletteParser;
use ZxImage\Plugin\Ulaplus\UlaplusPixelRenderer;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Ulaplus implements PluginInterface
{
    private PluginRuntime $runtime;
    private StandardScreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = 6976;
        $this->pipeline = new StandardScreenPipeline();
    }

    public function convert(): ?string
    {
        $rawScreen = (new UlaplusLoader())->load($this->runtime);
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        $attributes = (new UlaplusAttributeParser())->parse($rawScreen->attributesBytes, $this->runtime->width);
        $pixelsData = (new PixelParser($this->runtime->width))->parse($rawScreen->pixelsBytes);
        $colorOverrides = (new UlaplusPaletteParser())->parse($rawScreen->borderBytes, $colorTable->config);
        $parsedScreen = new ParsedScreen($pixelsData, $attributes, $colorOverrides);

        $image = (new UlaplusPixelRenderer())->render(
            $parsedScreen,
            $this->runtime->width,
            $this->runtime->height,
            $this->runtime->attributeWidth,
            $this->runtime->attributeHeight,
        );

        $image = $this->pipeline->finalizeImage($image, $colorTable, $this->runtime);
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
