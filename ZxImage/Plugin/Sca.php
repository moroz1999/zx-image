<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Sca\ScaLoader;
use ZxImage\Plugin\Sca\ScaRenderer;
use ZxImage\Plugin\Sca\ScaScreenParser;
use ZxImage\Service\PluginServices;

final class Sca implements FramePluginInterface
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
        $this->geometry = new PluginGeometry();
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
        $scaData = (new ScaLoader())->loadFrom($this->input, $this->geometry, $this->renderSettings, $this->services);
        if ($scaData === null) {
            return null;
        }

        $this->geometry = $scaData->geometry;
        $renderSettings = $scaData->renderSettings;
        $colorTable = $this->services->paletteService->buildColorTable($renderSettings->paletteString);
        $frames = [];
        $renderer = new ScaRenderer();
        $parser = new ScaScreenParser();

        foreach ($scaData->screens as $i => $rawScreen) {
            $parsedScreen = $parser->parseScreen($rawScreen, $this->geometry->width);
            $image = $renderer->render($parsedScreen, $colorTable, $this->geometry);
            $frames[] = new Frame($image, $scaData->delays[$i] ?? 0);
        }

        return new FrameSet(
            $frames,
            $renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
