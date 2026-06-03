<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Sl2\Sl2DefaultPaletteFactory;
use ZxImage\Plugin\Sl2\Sl2Loader;
use ZxImage\Service\IndexedScreenRenderer;
use ZxImage\Service\PluginServices;

class Sl2 implements FramePluginInterface
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
        $this->geometry = new PluginGeometry();
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
        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $sl2Data = (new Sl2Loader())->loadFrom(
            $this->input,
            $this->geometry->width * $this->geometry->height,
            $this->services,
        );
        if ($sl2Data === null) {
            return null;
        }
        $paletteEntries = (new Sl2DefaultPaletteFactory())->create();

        $image = $this->renderer->renderFrame(
            $sl2Data->pixelsBytes,
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
