<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Grf\GrfAspectScaler;
use ZxImage\Plugin\Grf\GrfLoader;
use ZxImage\Plugin\Grf\GrfPaletteParser;
use ZxImage\Plugin\Grf\GrfPixelParser;
use ZxImage\Service\PixelCanvas;
use ZxImage\Service\PluginServices;

final class Grf implements FramePluginInterface
{
    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry(usesBorder: false);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    #[Override]
    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    #[Override]
    public function convertFrames(): ?FrameSet
    {
        $grfData = (new GrfLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($grfData === null) {
            return null;
        }

        $this->geometry = $grfData->geometry;
        $pixelsData = (new GrfPixelParser())->parse(
            $grfData->pixelsArray,
            $grfData->attributesArray,
            $this->geometry->width,
        );
        $colors = (new GrfPaletteParser())->parse($grfData->paletteBytes);

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
