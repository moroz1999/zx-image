<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderGeometry;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Bmc4\Bmc4Loader;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\BscBorderRenderer;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Bmc4 implements FramePluginInterface
{
    private PluginRuntime $runtime;
    private StandardScreenPipeline $pipeline;

    private const int FILE_SIZE = 11904;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents);
        $this->runtime->borderWidth = 64;
        $this->runtime->borderHeight = 56;
        $this->runtime->attributeHeight = 4;
        $this->runtime->requiredFileSize = self::FILE_SIZE;
        $this->pipeline = new StandardScreenPipeline();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->runtime->applyRenderSettings($settings);
    }

    public function convertFrames(): ?FrameSet
    {
        $frameSet = $this->pipeline->buildFrameSetUsing(
            $this->runtime,
            fn(): ?RawScreen => (new Bmc4Loader())->load($this->runtime),
            fn(RawScreen $rawScreen): ParsedScreen => new ParsedScreen(
                (new PixelParser($this->runtime->width))->parse($rawScreen->pixelsBytes),
                (new AttributeParser($this->runtime->width))->parse($rawScreen->attributesBytes),
                [],
                $rawScreen->borderBytes,
            ),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderFrame(
                $parsedScreen,
                $colorTable,
                $flashedImage,
            ),
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

    private function renderFrame(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $image = (new PixelRenderer())->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $this->runtime->width,
            $this->runtime->height,
            $this->runtime->attributeWidth,
            $this->runtime->attributeHeight,
        );

        return (new BscBorderRenderer())->render(
            $image,
            $parsedScreen,
            $colorTable,
            $this->runtime->renderSettings->border,
            $this->runtime->width,
            $this->runtime->height,
            $this->runtime->borderWidth,
            $this->runtime->borderHeight,
        );
    }

    private function getFrameGeometry(): RenderGeometry
    {
        if ($this->runtime->renderSettings->border === null) {
            return new RenderGeometry($this->runtime->width, $this->runtime->height, 0, 0, false);
        }

        return new RenderGeometry(
            $this->runtime->width + $this->runtime->borderWidth * 2,
            $this->runtime->height + $this->runtime->borderHeight * 2,
            0,
            0,
            false,
        );
    }
}
