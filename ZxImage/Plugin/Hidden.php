<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Standard\HiddenPixelRenderer;
use ZxImage\Service\PluginServices;
use ZxImage\Service\StandardScreenPipeline;

class Hidden implements FramePluginInterface
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
        $this->geometry = new PluginGeometry();
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
        return $this->pipeline->buildFrameSetUsing(
            null,
            fn(): ?RawScreen => $this->pipeline->loadBitsFor($this->input, $this->geometry, $this->services),
            fn(RawScreen $rawScreen): ParsedScreen => $this->pipeline->parseScreen($rawScreen, $this->geometry->width),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderFrame(
                $parsedScreen,
                $colorTable,
                $flashedImage,
            ),
            $this->renderSettings,
            $this->services,
            $this->geometry,
        );
    }

    private function renderFrame(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        return (new HiddenPixelRenderer())->render(
            $parsedScreen,
            $colorTable,
            $flashedImage,
            $this->geometry->width,
            $this->geometry->height,
            $this->geometry->attributeWidth,
            $this->geometry->attributeHeight,
        );
    }
}
