<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Plugin\Standard\FlashPixelRenderer;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Flash implements PluginInterface
{
    private PluginRuntime $runtime;
    private StandardScreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->pipeline = new StandardScreenPipeline();
    }

    public function convert(): ?string
    {
        $rawScreen = $this->pipeline->loadBits($this->runtime);
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        $parsedScreen = $this->pipeline->parseScreen($rawScreen, $this->runtime->width);
        $image = (new FlashPixelRenderer())->render(
            $parsedScreen,
            $colorTable,
            $this->runtime->width,
            $this->runtime->height,
            $this->runtime->attributeWidth,
            $this->runtime->attributeHeight,
        );
        $image = $this->pipeline->finalizeImage($image, $colorTable, $this->runtime);

        $this->runtime->resultMime = 'image/gif';
        return $this->runtime->imageEncoder->toGif($image);
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
