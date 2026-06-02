<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\IndexedPaletteEntry;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Service\IndexedScreenRenderer;
use ZxImage\Service\PluginServices;

class Nxi implements FramePluginInterface
{
    protected const int PALETTE_LENGTH = 256;

    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;
    private IndexedScreenRenderer $renderer;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = (new PluginGeometry())->withRequiredFileSize(49664);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
        $this->renderer = new IndexedScreenRenderer();
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

        $paletteEntries = [];
        for ($i = 0; $i < static::PALETTE_LENGTH; $i++) {
            $paletteEntries[] = new IndexedPaletteEntry($reader->readByte() ?? 0, $reader->readByte() ?? 0);
        }
        $pixelsBytes = $reader->readBytes($this->geometry->width * $this->geometry->height);

        $image = $this->renderer->renderFrame(
            $pixelsBytes,
            $paletteEntries,
            $colorTable,
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
