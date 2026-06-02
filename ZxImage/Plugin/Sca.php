<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Sca\ScaRenderer;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Service\PluginServices;

class Sca implements FramePluginInterface
{
    private const int PIXELS_SIZE = 6144;
    private const int ATTRIBUTES_SIZE = 768;

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
        $this->geometry = new PluginGeometry();
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

        $signature = $reader->readString(3);
        if ($signature !== 'SCA') {
            return null;
        }

        $version = $reader->readByte();
        if ($version !== 1) {
            return null;
        }

        $this->geometry = $this->geometry->withDimensions(
            $reader->readWord() ?? $this->geometry->width,
            $reader->readWord() ?? $this->geometry->height,
        );
        $renderSettings = $this->renderSettings->withBorder($reader->readByte());
        $framesAmount = $reader->readWord() ?? 0;
        $payloadType = $reader->readByte();
        if ($payloadType !== 0) {
            return null;
        }
        $dataPointer = $reader->readWord() ?? 0;
        $reader->seek($dataPointer);

        $delays = [];
        for ($i = 0; $i < $framesAmount; $i++) {
            $delays[] = (int)(($reader->readByte() ?? 0) * (100 / 50));
        }

        $colorTable = $this->services->paletteService->buildColorTable($renderSettings->paletteString);
        $frames = [];
        $renderer = new ScaRenderer();

        for ($i = 0; $i < $framesAmount; $i++) {
            $pixelsBytes = $reader->readBytes(self::PIXELS_SIZE);
            $attributesBytes = $reader->readBytes(self::ATTRIBUTES_SIZE);

            $pixelsData = (new PixelParser($this->geometry->width))->parse($pixelsBytes);
            $attributes = (new AttributeParser($this->geometry->width))->parse($attributesBytes);
            $parsedScreen = new ParsedScreen($pixelsData, $attributes);

            $image = $renderer->render($parsedScreen, $colorTable, $this->geometry);
            $frames[] = new Frame($image, $delays[$i]);
        }

        return new FrameSet(
            $frames,
            $renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
