<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Converter;
use ZxImage\Dto\ChrdData;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Chrd\ChrdLoader;
use ZxImage\Plugin\Standard\PixelRenderer;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginRuntime;

class Chrd implements PluginInterface
{
    private const int COLOR_TYPE_STANDARD = 9;
    private const int COLOR_TYPE_GIGASCREEN = 18;

    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->usesBorder = false;
    }

    #[Override]
    public function convert(): ?string
    {
        $chrdData = (new ChrdLoader())->load($this->runtime);
        if ($chrdData === null) {
            return null;
        }

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);

        if ($chrdData->colorType === self::COLOR_TYPE_STANDARD) {
            return $this->renderStandard($chrdData->screen1, $colorTable);
        }

        if ($chrdData->colorType === self::COLOR_TYPE_GIGASCREEN) {
            return $this->renderGigascreen($chrdData->screen1, $chrdData->screen2, $colorTable);
        }

        return null;
    }

    private function renderStandard(ParsedScreen $screen, ColorTable $colorTable): string
    {
        $hasFlash = count($screen->attributes->flashMap) > 0;

        if ($hasFlash) {
            $frame1 = $this->runtime->imageEncoder->toPaletteGif($this->renderSingleImage($screen, $colorTable, false));
            $frame2 = $this->runtime->imageEncoder->toPaletteGif($this->renderSingleImage($screen, $colorTable, true));
            $this->runtime->resultMime = 'image/gif';
            return $this->runtime->imageEncoder->toAnimatedGif([$frame1, $frame2], [32, 32]);
        }

        $this->runtime->resultMime = 'image/png';
        return $this->runtime->imageEncoder->toPng($this->renderSingleImage($screen, $colorTable, false));
    }

    private function renderSingleImage(ParsedScreen $screen, ColorTable $colorTable, bool $flashedImage): \GdImage
    {
        $image = (new PixelRenderer())->render(
            $screen,
            $flashedImage,
            $colorTable->colors,
            $this->runtime->width,
            $this->runtime->height,
            $this->runtime->attributeWidth,
            $this->runtime->attributeHeight,
        );

        $image = $this->runtime->imageProcessor->resize($image, $this->runtime->zoom, $this->runtime->preFilters, $this->runtime->postFilters);
        return $this->runtime->imageProcessor->rotate($image, $this->runtime->rotation);
    }

    private function renderGigascreen(ParsedScreen $screen1, ParsedScreen $screen2, ColorTable $colorTable): string
    {
        $pipeline = new GigascreenPipeline();
        $renderSingle = fn(ParsedScreen $screen, ColorTable $ct, bool $flashed): \GdImage => $this->renderSingleImage($screen, $ct, $flashed);
        $renderMerged = fn(ParsedScreen $s1, ParsedScreen $s2, ColorTable $ct, bool $flashed): \GdImage => $pipeline->renderMergedImage($s1, $s2, $ct, $flashed, $this->runtime);

        return $pipeline->buildFromParsedScreens($screen1, $screen2, $colorTable, $this->runtime, $renderSingle, $renderMerged);
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
