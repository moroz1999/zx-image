<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Grf\GrfAspectScaler;
use ZxImage\Plugin\Grf\GrfPaletteParser;
use ZxImage\Plugin\Grf\GrfPixelParser;
use ZxImage\Service\PixelCanvas;
use ZxImage\Service\PluginServices;

class Grf implements FramePluginInterface
{
    private const int PROFI_COLOR_FORMAT = 19;

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
        $this->geometry = new PluginGeometry(usesBorder: false);
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

        $this->geometry = $this->geometry->withDimensions(
            $reader->readWord() ?? $this->geometry->width,
            $reader->readWord() ?? $this->geometry->height,
        );
        $bitsPerPixel = $reader->readByte() ?? 4;
        $reader->readByte(); // amod
        $reader->readByte(); // bps lo
        $reader->readByte(); // bps hi
        $reader->readByte(); // hlen
        $format = $reader->readByte() ?? 0;

        $paletteBytes = [];
        if ($format === self::PROFI_COLOR_FORMAT) {
            $paletteBytes = $reader->readBytes(16);
            $reader->readBytes(102);
        } else {
            $reader->readBytes(118);
        }

        $pixelsArray = [];
        $attributesArray = [];
        $length = (int)($this->geometry->width * $this->geometry->height / $bitsPerPixel);
        do {
            $pixelsArray[] = $reader->readByte();
            $attributesArray[] = $reader->readByte();
        } while ($length = $length - 2);

        $pixelsData = (new GrfPixelParser())->parse($pixelsArray, $attributesArray, $this->geometry->width);
        $colors = (new GrfPaletteParser())->parse($paletteBytes);

        $image = (new PixelCanvas())->draw($pixelsData, $colors, $this->geometry->width, $this->geometry->height);
        $image = (new GrfAspectScaler())->scale($image);

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
