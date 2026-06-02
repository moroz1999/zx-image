<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Atmega\AtmegaPaletteParser;
use ZxImage\Plugin\Atmega\AtmegaPixelParser;
use ZxImage\Service\PixelCanvas;
use ZxImage\Service\PluginServices;

class Atmega implements FramePluginInterface
{
    private const int PIXEL_PAGE_SIZE = 8000;
    private const int FILE_SIZE_WITH_GAPS = 32896;
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
        $reader = $this->services->fileLoader->openSource(
            $this->input->sourceFilePath,
            $this->input->sourceFileContents,
            null,
        );
        if ($reader === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $fileSize = $reader->getSize();

        $pixelsArray = [];
        if ($fileSize === self::FILE_SIZE_WITH_GAPS) {
            for ($page = 0; $page < 4; $page++) {
                $pixelsArray = array_merge($pixelsArray, $reader->readBytes(self::PIXEL_PAGE_SIZE));
                $reader->readBytes(192);
            }
        } else {
            $pixelsArray = $reader->readBytes(self::PIXEL_PAGE_SIZE * 4);
        }
        $reader->readBytes(21);

        $paletteBytes = [
            0b00000000,
            0b00000001,
            0b00000010,
            0b00000011,
            0b00010000,
            0b00010001,
            0b00010010,
            0b00010011,
            0b00000000,
            0b00100001,
            0b01000010,
            0b01100011,
            0b10010000,
            0b10110001,
            0b11010010,
            0b11110011,
        ];

        $colors = (new AtmegaPaletteParser())->parse($paletteBytes, $colorTable->config);
        $pixelsData = (new AtmegaPixelParser())->parse($pixelsArray, $this->geometry->width);

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
