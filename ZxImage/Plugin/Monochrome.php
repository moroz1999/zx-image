<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Monochrome\MonochromeLoader;
use ZxImage\Plugin\Monochrome\MonochromeRenderer;
use ZxImage\Plugin\Monochrome\MonochromeScreenParser;
use ZxImage\Service\PluginServices;

final class Monochrome implements FramePluginInterface
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
        $this->geometry = (new PluginGeometry())->withRequiredFileSize(6144);
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
        $rawScreen = (new MonochromeLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $parsedScreen = (new MonochromeScreenParser())->parse(
            $rawScreen,
            $this->geometry->width,
            $this->geometry->height,
        );
        $image = (new MonochromeRenderer())->render($parsedScreen, $colorTable, $this->geometry);

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
