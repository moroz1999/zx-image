<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Nxi\NxiLoader;
use ZxImage\Service\IndexedScreenRenderer;
use ZxImage\Service\PluginServices;

class Nxi implements FramePluginInterface
{
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
        $nxiData = (new NxiLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($nxiData === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

        $image = $this->renderer->renderFrame(
            $nxiData->pixelsBytes,
            $nxiData->paletteEntries,
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
