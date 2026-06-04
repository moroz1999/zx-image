<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Stellar\StellarLoader;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginServices;

final class Stellar implements FramePluginInterface
{
    private const int REQUIRED_FILE_SIZE = 3072;
    private const int ATTRIBUTE_HEIGHT = 4;

    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;
    private GigascreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = (new PluginGeometry(requiredFileSize: self::REQUIRED_FILE_SIZE))
            ->withAttributeHeight(self::ATTRIBUTE_HEIGHT);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
        $this->pipeline = new GigascreenPipeline();
    }

    #[Override]
    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    #[Override]
    public function convertFrames(): ?FrameSet
    {
        return $this->pipeline->buildFrameSetWithDefaultRenderingFor(
            $this->geometry,
            $this->renderSettings,
            $this->services,
            fn(): ?DualRawScreen => (new StellarLoader())->loadFrom($this->input, $this->geometry, $this->services),
        );
    }
}
