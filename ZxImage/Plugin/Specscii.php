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
use ZxImage\Plugin\Specscii\SpecsciiLoader;
use ZxImage\Service\PluginServices;
use ZxImage\Service\StandardScreenPipeline;

class Specscii implements FramePluginInterface
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
            fn(): ?RawScreen => (new SpecsciiLoader())->loadFrom($this->input, $this->services),
            fn(RawScreen $rawScreen): ParsedScreen => $this->pipeline->parseScreen($rawScreen, $this->geometry->width),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->pipeline->renderFrame(
                $parsedScreen,
                $colorTable,
                $flashedImage,
                $this->geometry,
            ),
            $this->renderSettings,
            $this->services,
            $this->geometry,
        );
    }
}
