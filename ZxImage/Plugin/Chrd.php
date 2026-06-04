<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use Override;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Chrd\ChrdData;
use ZxImage\Plugin\Chrd\ChrdLoader;
use ZxImage\Plugin\Chrd\ChrdRenderer;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginServices;

final class Chrd implements FramePluginInterface
{
    private const int COLOR_TYPE_STANDARD = 9;
    private const int COLOR_TYPE_GIGASCREEN = 18;

    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry(usesBorder: false);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    #[Override]
    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    #[Override]
    public function convertFrames(): ?FrameSet
    {
        $chrdData = (new ChrdLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($chrdData === null) {
            return null;
        }

        $this->geometry = $chrdData->geometry;
        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

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
        $renderer = new ChrdRenderer();

        if ($hasFlash) {
            return new FrameSet(
                [
                    new Frame($renderer->render($screen, $colorTable, false, $this->geometry), 32),
                    new Frame($renderer->render($screen, $colorTable, true, $this->geometry), 32),
                ],
                $this->renderSettings,
                $this->geometry->toRenderGeometry(),
                $colorTable,
            );
        }

        return new FrameSet(
            [new Frame($renderer->render($screen, $colorTable, false, $this->geometry))],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }

    private function buildGigascreenFrameSet(ParsedScreen $screen1, ParsedScreen $screen2, ColorTable $colorTable): FrameSet
    {
        $pipeline = new GigascreenPipeline();
        $renderer = new ChrdRenderer();
        $renderSingle = fn(ParsedScreen $screen, ColorTable $ct, bool $flashed): GdImage => $renderer->render($screen, $ct, $flashed, $this->geometry);
        $renderMerged = fn(ParsedScreen $s1, ParsedScreen $s2, ColorTable $ct, bool $flashed): GdImage => $pipeline->renderMergedFrame($s1, $s2, $ct, $flashed, $this->geometry);

        return $pipeline->buildFrameSetFromParsedScreens(
            $screen1,
            $screen2,
            $colorTable,
            $renderSingle,
            $renderMerged,
            $this->renderSettings,
            $this->geometry,
        );
    }
}
