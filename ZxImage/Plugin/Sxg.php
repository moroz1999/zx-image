<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Sxg\SxgLoader;
use ZxImage\Plugin\Sxg\SxgPaletteParser;
use ZxImage\Plugin\Sxg\SxgPixelParser;
use ZxImage\Plugin\Sxg\SxgRenderer;
use ZxImage\Service\PluginServices;

final class Sxg implements FramePluginInterface
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
        $sxgData = (new SxgLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($sxgData === null) {
            return null;
        }

        $this->geometry = $sxgData->geometry;
        $colors = (new SxgPaletteParser())->parse($sxgData->paletteWords);
        $pixelsData = (new SxgPixelParser())->parse($sxgData->pixelsBytes, $sxgData->format, $this->geometry->width);
        $image = (new SxgRenderer())->render($pixelsData, $colors, $this->geometry->width, $this->geometry->height);

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
