<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Tricolor\TricolorMixer;
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
        $reader = $this->services->fileLoader->openSource(
            $this->input->sourceFilePath,
            $this->input->sourceFileContents,
            $this->geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $pipeline = new StandardScreenPipeline();

        $screenColors = [
            [10, 0],
            [12, 0],
            [9, 0],
        ];

        $screens = [];
        for ($i = 0; $i < 3; $i++) {
            $pixelsBytes = $reader->readBytes(6144);
            [$inkKey, $paperKey] = $screenColors[$i];
            $rows = (int)($this->geometry->height / 8);
            $cols = (int)($this->geometry->width / 8);
            $attributes = new AttributeMap(
                array_fill(0, $rows, array_fill(0, $cols, $inkKey)),
                array_fill(0, $rows, array_fill(0, $cols, $paperKey)),
                [],
            );
            $pixelsData = (new PixelParser($this->geometry->width))->parse($pixelsBytes);
            $screens[] = new ParsedScreen($pixelsData, $attributes);
        }

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
