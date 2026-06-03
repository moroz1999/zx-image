<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Tricolor\TricolorLoader;
use ZxImage\Plugin\Tricolor\TricolorMixer;
use ZxImage\Plugin\Tricolor\TricolorScreenParser;
use ZxImage\Service\PluginServices;
use ZxImage\Service\StandardScreenPipeline;

class Tricolor implements FramePluginInterface
{
    private const int REQUIRED_FILE_SIZE = 18432;

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
        $this->geometry = new PluginGeometry(requiredFileSize: self::REQUIRED_FILE_SIZE);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $tricolorData = (new TricolorLoader())->loadFrom($this->input, self::REQUIRED_FILE_SIZE, $this->services);
        if ($tricolorData === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $pipeline = new StandardScreenPipeline();
        $screens = (new TricolorScreenParser())->parse(
            $tricolorData->screenPixelsBytes,
            $this->geometry->width,
            $this->geometry->height,
        );

        if ($this->renderSettings->gigascreenMode === 'flicker') {
            $frames = [];
            foreach ($screens as $screen) {
                $frames[] = new Frame($pipeline->renderFrame($screen, $colorTable, false, $this->geometry), 2);
            }

            return new FrameSet(
                $frames,
                $this->renderSettings,
                $this->geometry->toRenderGeometry(),
                $colorTable,
            );
        }

        $resources = [];
        foreach ($screens as $screen) {
            $resources[] = $pipeline->renderFrame($screen, $colorTable, false, $this->geometry);
        }

        return new FrameSet(
            [new Frame((new TricolorMixer())->mix($resources))],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
