<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Ssx\SsxPluginResolver;
use ZxImage\Service\PluginServices;

class Ssx implements FramePluginInterface
{
    private PluginInput $input;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $type = (new SsxPluginResolver())->resolveType($this->input, $this->services);
        if ($type === null) {
            return null;
        }

        $plugin = new $type($this->input->sourceFilePath, $this->input->sourceFileContents);
        if (!$plugin instanceof FramePluginInterface) {
            return null;
        }

        $plugin->configure($this->renderSettings);
        return $plugin->convertFrames();
    }
}
