<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use Override;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Attributes\AttributesLoader;
use ZxImage\Plugin\Attributes\AttributesScreenParser;
use ZxImage\Service\PluginServices;
use ZxImage\Service\StandardScreenPipeline;

final class Attributes implements FramePluginInterface
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
        $this->geometry = (new PluginGeometry())->withRequiredFileSize(768);
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
        return $this->pipeline->buildFrameSetUsing(
            fn(): ?RawScreen => (new AttributesLoader())->loadFrom($this->input, $this->geometry, $this->services),
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen),
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

    private function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        return (new AttributesScreenParser())->parse($rawScreen, $this->geometry->width, $this->geometry->height);
    }
}
