<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderGeometry;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Bsp\BspData;
use ZxImage\Plugin\Bsp\BspLoader;
use ZxImage\Plugin\Bsp\BspRenderer;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginServices;

class Bsp implements FramePluginInterface
{
    private const int BORDER_WIDTH = 64;
    private const int BORDER_HEIGHT = 64;
    private const int BORDER_HEIGHT_BOTTOM = 48;

    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry(
            borderWidth: self::BORDER_WIDTH,
            borderHeight: self::BORDER_HEIGHT,
        );
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $bspData = (new BspLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($bspData === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $pipeline = new GigascreenPipeline();
        $frameSet = $this->buildFrameSet($bspData, $colorTable, $pipeline);

        return new FrameSet(
            $frameSet->frames,
            $frameSet->renderSettings,
            $this->getFrameGeometry(),
            $frameSet->colorTable,
            $frameSet->interlaceLineHeight,
        );
    }

    private function buildFrameSet(
        BspData $bspData,
        ColorTable $colorTable,
        GigascreenPipeline $pipeline,
    ): FrameSet {
        $renderer = new BspRenderer();

        $renderSingle = fn(ParsedScreen $screen, ColorTable $ct, bool $flashedImage) => $renderer->renderSingle(
            $bspData,
            $screen,
            $ct,
            $flashedImage,
            $this->geometry,
        );

        $renderMerged = fn(ParsedScreen $s1, ParsedScreen $s2, ColorTable $ct, bool $flashedImage) => $renderer->renderMerged(
            $bspData,
            $s1,
            $s2,
            $ct,
            $flashedImage,
            $this->geometry,
        );

        return $pipeline->buildFrameSetFromParsedScreens(
            $bspData->screen1,
            $bspData->screen2,
            $colorTable,
            $renderSingle,
            $renderMerged,
            $this->renderSettings,
            $this->geometry,
        );
    }

    private function getFrameGeometry(): RenderGeometry
    {
        return new RenderGeometry(
            $this->geometry->width + $this->geometry->borderWidth * 2,
            $this->geometry->height + $this->geometry->borderHeight + self::BORDER_HEIGHT_BOTTOM,
            0,
            0,
            false,
        );
    }
}
