<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderSettings;

final readonly class GigascreenPipeline
{
    public function __construct(
        private GigascreenFrameSetBuilder $frameSetBuilder = new GigascreenFrameSetBuilder(),
        private GigascreenScreenParser $screenParser = new GigascreenScreenParser(),
        private GigascreenFrameRenderer $frameRenderer = new GigascreenFrameRenderer(),
    ) {
    }

    /**
     * @param callable(): ?DualRawScreen $loadBits
     */
    public function buildFrameSetWithDefaultRenderingFor(
        PluginGeometry $geometry,
        RenderSettings $renderSettings,
        PluginServices $services,
        callable $loadBits,
    ): ?FrameSet {
        return $this->buildFrameSetUsing(
            $loadBits,
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen, $geometry),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderFrame(
                $parsedScreen,
                $colorTable,
                $flashedImage,
                $geometry,
            ),
            fn(ParsedScreen $first, ParsedScreen $second, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderMergedFrame(
                $first,
                $second,
                $colorTable,
                $flashedImage,
                $geometry,
            ),
            $renderSettings,
            $services,
            $geometry,
        );
    }

    /**
     * @param callable(): ?DualRawScreen $loadBits
     * @param callable(RawScreen): ParsedScreen $parseScreen
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderFrame
     * @param callable(ParsedScreen, ParsedScreen, ColorTable, bool): GdImage $renderMergedFrame
     */
    public function buildFrameSetUsing(
        callable $loadBits,
        callable $parseScreen,
        callable $renderFrame,
        callable $renderMergedFrame,
        RenderSettings $renderSettings,
        PluginServices $services,
        PluginGeometry $geometry,
    ): ?FrameSet {
        $dualRawScreen = $loadBits();
        if ($dualRawScreen === null) {
            return null;
        }

        $colorTable = $services->paletteService->buildColorTable($renderSettings->paletteString);
        $parsedScreen1 = $parseScreen($dualRawScreen->first);
        $parsedScreen2 = $parseScreen($dualRawScreen->second);

        return $this->frameSetBuilder->build(
            $parsedScreen1,
            $parsedScreen2,
            $colorTable,
            $renderFrame,
            $renderMergedFrame,
            $renderSettings,
            $geometry,
        );
    }

    /**
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderFrame
     * @param callable(ParsedScreen, ParsedScreen, ColorTable, bool): GdImage $renderMergedFrame
     */
    public function buildFrameSetFromParsedScreens(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        callable $renderFrame,
        callable $renderMergedFrame,
        RenderSettings $renderSettings,
        PluginGeometry $geometry,
    ): FrameSet {
        return $this->frameSetBuilder->build(
            $parsedScreen1,
            $parsedScreen2,
            $colorTable,
            $renderFrame,
            $renderMergedFrame,
            $renderSettings,
            $geometry,
        );
    }

    public function parseScreen(RawScreen $rawScreen, PluginGeometry $runtime): ParsedScreen
    {
        return $this->screenParser->parse($rawScreen, $runtime);
    }

    public function renderFrame(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $runtime,
    ): GdImage {
        return $this->frameRenderer->renderSingle(
            $parsedScreen,
            $colorTable,
            $flashedImage,
            $runtime,
        );
    }

    public function renderMergedFrame(
        ParsedScreen $parsedScreen1,
        ParsedScreen $parsedScreen2,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $runtime,
    ): GdImage {
        return $this->frameRenderer->renderMerged(
            $parsedScreen1,
            $parsedScreen2,
            $colorTable,
            $flashedImage,
            $runtime,
        );
    }

}
