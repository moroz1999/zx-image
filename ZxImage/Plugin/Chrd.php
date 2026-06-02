<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use Override;
use ZxImage\Converter;
use ZxImage\Dto\ChrdData;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Chrd\ChrdLoader;
use ZxImage\Plugin\Standard\PixelRenderer;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginRuntime;

class Chrd implements FramePluginInterface
{
    private const int COLOR_TYPE_STANDARD = 9;
    private const int COLOR_TYPE_GIGASCREEN = 18;

    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents);
        $this->runtime->usesBorder = false;
    }

    public function configure(RenderSettings $settings): void
    {
        $this->runtime->applyRenderSettings($settings);
    }

    #[Override]
    public function convertFrames(): ?FrameSet
    {
        $chrdData = (new ChrdLoader())->load($this->runtime);
        if ($chrdData === null) {
            return null;
        }

        $colorTable = $this->runtime->services->paletteService->buildColorTable($this->runtime->renderSettings->paletteString);

        if ($chrdData->colorType === self::COLOR_TYPE_STANDARD) {
            return $this->buildStandardFrameSet($chrdData->screen1, $colorTable);
        }

        if ($chrdData->colorType === self::COLOR_TYPE_GIGASCREEN) {
            return $this->buildGigascreenFrameSet($chrdData->screen1, $chrdData->screen2, $colorTable);
        }

        return null;
    }

    private function buildStandardFrameSet(ParsedScreen $screen, ColorTable $colorTable): FrameSet
    {
        $hasFlash = count($screen->attributes->flashMap) > 0;

        if ($hasFlash) {
            return new FrameSet(
                [
                    new Frame($this->renderSingleFrame($screen, $colorTable, false), 32),
                    new Frame($this->renderSingleFrame($screen, $colorTable, true), 32),
                ],
                $this->runtime->renderSettings,
                $this->runtime->getRenderGeometry(),
                $colorTable,
            );
        }

        return new FrameSet(
            [new Frame($this->renderSingleFrame($screen, $colorTable, false))],
            $this->runtime->renderSettings,
            $this->runtime->getRenderGeometry(),
            $colorTable,
        );
    }

    private function renderSingleFrame(ParsedScreen $screen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        return (new PixelRenderer())->render(
            $screen,
            $flashedImage,
            $colorTable->colors,
            $this->runtime->width,
            $this->runtime->height,
            $this->runtime->attributeWidth,
            $this->runtime->attributeHeight,
        );
    }

    private function buildGigascreenFrameSet(ParsedScreen $screen1, ParsedScreen $screen2, ColorTable $colorTable): FrameSet
    {
        $pipeline = new GigascreenPipeline();
        $renderSingle = fn(ParsedScreen $screen, ColorTable $ct, bool $flashed): GdImage => $this->renderSingleFrame($screen, $ct, $flashed);
        $renderMerged = fn(ParsedScreen $s1, ParsedScreen $s2, ColorTable $ct, bool $flashed): GdImage => $pipeline->renderMergedFrame($s1, $s2, $ct, $flashed, $this->runtime);

        return $pipeline->buildFrameSetFromParsedScreens($screen1, $screen2, $colorTable, $this->runtime, $renderSingle, $renderMerged);
    }
}
