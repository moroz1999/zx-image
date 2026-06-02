<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Atmega\AtmegaLoader;
use ZxImage\Plugin\Atmega\AtmegaPaletteParser;
use ZxImage\Plugin\Atmega\AtmegaPixelParser;
use ZxImage\Service\PixelCanvas;
use ZxImage\Service\PluginServices;

class Atmega implements FramePluginInterface
{
    private const int WIDTH = 320;
    private const int HEIGHT = 200;

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
        $this->geometry = (new PluginGeometry())->withDimensions(self::WIDTH, self::HEIGHT);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $atmegaData = (new AtmegaLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($atmegaData === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

        $colors = (new AtmegaPaletteParser())->parse($atmegaData->paletteBytes, $colorTable->config);
        $pixelsData = (new AtmegaPixelParser())->parse($atmegaData->pixelsArray, $this->geometry->width);

        $image = (new PixelCanvas())->draw(
            $pixelsData,
            $colors,
            $this->geometry->width,
            $this->geometry->height,
        );

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
