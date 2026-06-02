<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Tricolor\TricolorMixer;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Tricolor implements FramePluginInterface
{
    private const int REQUIRED_FILE_SIZE = 18432;

    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents);
    }

    public function configure(RenderSettings $settings): void
    {
        $this->runtime->applyRenderSettings($settings);
    }

    public function convertFrames(): ?FrameSet
    {
        $reader = $this->runtime->services->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            self::REQUIRED_FILE_SIZE,
        );
        if ($reader === null) {
            return null;
        }

        $colorTable = $this->runtime->services->paletteService->buildColorTable($this->runtime->renderSettings->paletteString);
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
            $rows = (int)($this->runtime->height / 8);
            $cols = (int)($this->runtime->width / 8);
            $attributes = new AttributeMap(
                array_fill(0, $rows, array_fill(0, $cols, $inkKey)),
                array_fill(0, $rows, array_fill(0, $cols, $paperKey)),
                [],
            );
            $pixelsData = (new PixelParser($this->runtime->width))->parse($pixelsBytes);
            $screens[] = new ParsedScreen($pixelsData, $attributes);
        }

        if ($this->runtime->renderSettings->gigascreenMode === 'flicker') {
            $frames = [];
            foreach ($screens as $screen) {
                $frames[] = new Frame($pipeline->renderFrame($screen, $colorTable, false, $this->runtime), 2);
            }

            return new FrameSet(
                $frames,
                $this->runtime->renderSettings,
                $this->runtime->getRenderGeometry(),
                $colorTable,
            );
        }

        $resources = [];
        foreach ($screens as $screen) {
            $resources[] = $pipeline->renderFrame($screen, $colorTable, false, $this->runtime);
        }

        return new FrameSet(
            [new Frame((new TricolorMixer())->mix($resources))],
            $this->runtime->renderSettings,
            $this->runtime->getRenderGeometry(),
            $colorTable,
        );
    }
}
