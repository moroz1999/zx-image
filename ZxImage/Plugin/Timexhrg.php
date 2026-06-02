<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Timexhr\TimexhrAttributeBuilder;
use ZxImage\Plugin\Timexhrg\TimexhrgLoader;
use ZxImage\Plugin\Timexhrg\TimexhrgPixelRenderer;
use ZxImage\Service\PluginServices;

class Timexhrg implements FramePluginInterface
{
    private const int REQUIRED_FILE_SIZE = 24578;
    private const int WIDTH = 512;
    private const int HEIGHT = 384;

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
        $this->geometry = (new PluginGeometry(requiredFileSize: self::REQUIRED_FILE_SIZE))
            ->withDimensions(self::WIDTH, self::HEIGHT);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $dualRawScreen = (new TimexhrgLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($dualRawScreen === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $parsedScreen1 = $this->parseRawScreen($dualRawScreen->first);
        $parsedScreen2 = $this->parseRawScreen($dualRawScreen->second);
        $renderer = new TimexhrgPixelRenderer();

        if ($this->isFlickerMode()) {
            return $this->buildFlickerFrameSet($parsedScreen1, $parsedScreen2, $colorTable, $renderer);
        }

        return new FrameSet(
            [new Frame($this->renderMergedFrame($parsedScreen1, $parsedScreen2, $colorTable, $renderer))],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }

    private function parseRawScreen(RawScreen $rawScreen): ParsedScreen
    {
        return new ParsedScreen(
            (new PixelParser($this->geometry->width))->parse($rawScreen->pixelsBytes),
            (new TimexhrAttributeBuilder())->build(
                $rawScreen->attributesBytes[0] ?? 0,
                $this->geometry->width,
                $this->geometry->height,
            ),
        );
    }

    private function buildFlickerFrameSet(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        TimexhrgPixelRenderer $renderer,
    ): FrameSet {
        return new FrameSet(
            [
                new Frame(
                    $this->renderSingleFrame($parsedScreen1, $colorTable, $renderer),
                    2,
                    $this->getFrameRenderSettings($parsedScreen1),
                ),
                new Frame(
                    $this->renderSingleFrame($parsedScreen2, $colorTable, $renderer),
                    2,
                    $this->getFrameRenderSettings($parsedScreen2),
                ),
            ],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
            $this->getInterlaceLineHeight(),
        );
    }

    private function renderSingleFrame(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        TimexhrgPixelRenderer $renderer,
    ): GdImage {
        return $renderer->renderSingle($parsedScreen, $colorTable, $this->geometry->width, $this->geometry->height);
    }

    private function renderMergedFrame(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        TimexhrgPixelRenderer $renderer,
    ): GdImage {
        return $renderer->renderMerged(
            $parsedScreen1,
            $parsedScreen2,
            $colorTable,
            $this->geometry->width,
            $this->geometry->height,
        );
    }

    private function getFrameRenderSettings(ParsedScreen $parsedScreen): RenderSettings
    {
        return $this->renderSettings->withBorder($parsedScreen->attributes->paperMap[0][0]);
    }

    private function isFlickerMode(): bool
    {
        return $this->renderSettings->gigascreenMode === 'flicker'
            || $this->renderSettings->gigascreenMode === 'interlace1'
            || $this->renderSettings->gigascreenMode === 'interlace2';
    }

    private function getInterlaceLineHeight(): ?int
    {
        if ($this->renderSettings->gigascreenMode === 'interlace1') {
            return 1;
        }

        if ($this->renderSettings->gigascreenMode === 'interlace2') {
            return 2;
        }

        return null;
    }

}
