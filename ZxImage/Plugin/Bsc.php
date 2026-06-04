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
use ZxImage\Dto\RenderGeometry;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Bsc\BscLoader;
use ZxImage\Plugin\Standard\BscFrameRenderer;
use ZxImage\Plugin\Standard\StandardScreenParser;
use ZxImage\Service\PluginServices;
use ZxImage\Service\StandardScreenPipeline;

final class Bsc implements FramePluginInterface
{
    private const int FILE_SIZE = 11136;
    private const int BORDER_WIDTH = 64;
    private const int BORDER_HEIGHT = 56;

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
        $this->geometry = new PluginGeometry(
            borderWidth: self::BORDER_WIDTH,
            borderHeight: self::BORDER_HEIGHT,
            requiredFileSize: self::FILE_SIZE,
        );
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
        $renderer = new BscFrameRenderer();
        $screenParser = new StandardScreenParser();
        $frameSet = $this->pipeline->buildFrameSetUsing(
            fn(): ?RawScreen => (new BscLoader())->loadFrom($this->input, $this->geometry, $this->services),
            fn(RawScreen $rawScreen): ParsedScreen => $screenParser->parse($rawScreen, $this->geometry->width),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $renderer->render(
                $parsedScreen,
                $colorTable,
                $flashedImage,
                $this->renderSettings->border,
                $this->geometry,
            ),
            $this->renderSettings,
            $this->services,
            $this->geometry,
        );

        if ($frameSet === null) {
            return null;
        }

        return new FrameSet(
            $frameSet->frames,
            $frameSet->renderSettings,
            $this->getFrameGeometry(),
            $frameSet->colorTable,
            $frameSet->interlaceLineHeight,
        );
    }

    private function getFrameGeometry(): RenderGeometry
    {
        if ($this->renderSettings->border === null) {
            return new RenderGeometry($this->geometry->width, $this->geometry->height, 0, 0, false);
        }

        return new RenderGeometry(
            $this->geometry->width + $this->geometry->borderWidth * 2,
            $this->geometry->height + $this->geometry->borderHeight * 2,
            0,
            0,
            false,
        );
    }
}
