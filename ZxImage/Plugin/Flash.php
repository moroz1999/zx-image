<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Standard\FlashPixelRenderer;
use ZxImage\Service\PluginServices;
use ZxImage\Service\StandardScreenPipeline;

final class Flash implements FramePluginInterface
{
    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;
    private StandardScreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry();
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
        $this->pipeline = new StandardScreenPipeline();
    }

    #[Override]
    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    #[Override]
    public function convertFrames(): ?FrameSet
    {
        $rawScreen = $this->pipeline->loadBitsFor($this->input, $this->geometry, $this->services);
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $parsedScreen = $this->pipeline->parseScreen($rawScreen, $this->geometry->width);
        $image = (new FlashPixelRenderer())->render(
            $parsedScreen,
            $colorTable,
            $this->geometry->width,
            $this->geometry->height,
            $this->geometry->attributeWidth,
            $this->geometry->attributeHeight,
        );

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
