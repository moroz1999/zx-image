<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Ulaplus\UlaplusAttributeParser;
use ZxImage\Plugin\Ulaplus\UlaplusLoader;
use ZxImage\Plugin\Ulaplus\UlaplusPaletteParser;
use ZxImage\Plugin\Ulaplus\UlaplusPixelRenderer;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Ulaplus implements FramePluginInterface
{
    private PluginRuntime $runtime;
    private StandardScreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents);
        $this->runtime->requiredFileSize = 6976;
        $this->pipeline = new StandardScreenPipeline();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->runtime->applyRenderSettings($settings);
    }

    public function convertFrames(): ?FrameSet
    {
        $rawScreen = (new UlaplusLoader())->load($this->runtime);
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->runtime->services->paletteService->buildColorTable($this->runtime->renderSettings->paletteString);
        $attributes = (new UlaplusAttributeParser())->parse($rawScreen->attributesBytes, $this->runtime->width);
        $pixelsData = (new PixelParser($this->runtime->width))->parse($rawScreen->pixelsBytes);
        $colorOverrides = (new UlaplusPaletteParser())->parse($rawScreen->borderBytes, $colorTable->config);
        $parsedScreen = new ParsedScreen($pixelsData, $attributes, $colorOverrides);

        $image = (new UlaplusPixelRenderer())->render(
            $parsedScreen,
            $this->runtime->width,
            $this->runtime->height,
            $this->runtime->attributeWidth,
            $this->runtime->attributeHeight,
        );

        return new FrameSet(
            [new Frame($image)],
            $this->runtime->renderSettings,
            $this->runtime->getRenderGeometry(),
            $colorTable,
        );
    }
}
