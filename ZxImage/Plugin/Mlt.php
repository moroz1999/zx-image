<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Service\PluginServices;
use ZxImage\Service\StandardScreenPipeline;

class Mlt implements FramePluginInterface
{
    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;
    private StandardScreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = (new PluginGeometry())
            ->withAttributeHeight(1)
            ->withRequiredFileSize(12288);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
        $this->pipeline = new StandardScreenPipeline();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        return $this->pipeline->buildFrameSetFor($this->input, $this->geometry, $this->renderSettings, $this->services);
    }
}
