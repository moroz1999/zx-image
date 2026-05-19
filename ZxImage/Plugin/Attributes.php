<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Attributes\AttributesLoader;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Attributes implements PluginInterface
{
    private PluginRuntime $runtime;
    private StandardScreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = 768;
        $this->pipeline = new StandardScreenPipeline();
    }

    public function convert(): ?string
    {
        return $this->pipeline->convertUsing(
            $this->runtime,
            fn(): ?RawScreen => (new AttributesLoader())->load($this->runtime),
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->pipeline->renderImage(
                $parsedScreen,
                $colorTable,
                $flashedImage,
                $this->runtime,
            ),
        );
    }

    private function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = (new AttributeParser($this->runtime->width))->parse($rawScreen->attributesBytes);
        $pixelsData = $this->generateCheckerboardPixels();
        return new ParsedScreen($pixelsData, $attributes);
    }

    private function generateCheckerboardPixels(): array
    {
        $pixelsData = [];
        for ($y = 0; $y < $this->runtime->height; $y++) {
            for ($x = 0; $x < $this->runtime->width; $x++) {
                $pixelsData[$y][$x] = ($x + $y) % 2;
            }
        }
        return $pixelsData;
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
