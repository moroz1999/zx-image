<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Monochrome\MonochromeLoader;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Monochrome implements PluginInterface
{
    private const int INK_KEY = 15;
    private const int PAPER_KEY = 8;

    private PluginRuntime $runtime;
    private StandardScreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = 6144;
        $this->pipeline = new StandardScreenPipeline();
    }

    public function convert(): ?string
    {
        $rawScreen = (new MonochromeLoader())->load($this->runtime);
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        $pixelsData = (new PixelParser($this->runtime->width))->parse($rawScreen->pixelsBytes);
        $rows = (int)($this->runtime->height / 8);
        $cols = (int)($this->runtime->width / 8);
        $attributes = new AttributeMap(
            array_fill(0, $rows, array_fill(0, $cols, self::INK_KEY)),
            array_fill(0, $rows, array_fill(0, $cols, self::PAPER_KEY)),
            [],
        );
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
