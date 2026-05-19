<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Timexhr\TimexhrAttributeBuilder;
use ZxImage\Plugin\Timexhrg\TimexhrgLoader;
use ZxImage\Plugin\Timexhrg\TimexhrgPixelRenderer;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginRuntime;

class Timexhrg implements PluginInterface
{
    private PluginRuntime $runtime;
    private GigascreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = 12289 * 2;
        $this->runtime->width = 512;
        $this->runtime->height = 384;
        $this->pipeline = new GigascreenPipeline();
    }

    public function convert(): ?string
    {
        $renderer = new TimexhrgPixelRenderer();
        return $this->pipeline->convertUsing(
            $this->runtime,
            fn(): ?DualRawScreen => (new TimexhrgLoader())->load($this->runtime),
            fn(RawScreen $rawScreen): ParsedScreen => new ParsedScreen(
                (new PixelParser($this->runtime->width))->parse($rawScreen->pixelsBytes),
                (new TimexhrAttributeBuilder())->build($rawScreen->attributesBytes[0] ?? 0, $this->runtime->width, $this->runtime->height),
            ),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderSingle(
                $parsedScreen,
                $colorTable,
                $renderer,
            ),
            fn(ParsedScreen $first, ParsedScreen $second, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderMerged(
                $first,
                $second,
                $colorTable,
                $renderer,
            ),
        );
    }

    private function renderSingle(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        TimexhrgPixelRenderer $renderer,
    ): GdImage {
        $image = $renderer->renderSingle($parsedScreen, $colorTable, $this->runtime->width, $this->runtime->height);
        $this->runtime->border = $parsedScreen->attributes->paperMap[0][0];
        return $this->pipeline->finalizeImage($image, $colorTable, $this->runtime);
    }

    private function renderMerged(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        TimexhrgPixelRenderer $renderer,
    ): GdImage {
        $image = $renderer->renderMerged($parsedScreen1, $parsedScreen2, $colorTable, $this->runtime->width, $this->runtime->height);
        return $this->pipeline->finalizeImage($image, $colorTable, $this->runtime);
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
