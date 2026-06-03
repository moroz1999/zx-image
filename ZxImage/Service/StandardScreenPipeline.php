<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderSettings;

final readonly class StandardScreenPipeline
{
    public function __construct(
        private StandardRawScreenLoader $rawScreenLoader = new StandardRawScreenLoader(),
        private StandardFrameSetBuilder $frameSetBuilder = new StandardFrameSetBuilder(),
        private StandardParsedScreenParser $parsedScreenParser = new StandardParsedScreenParser(),
        private StandardFrameRenderer $frameRenderer = new StandardFrameRenderer(),
    ) {
    }

    public function buildFrameSetFor(
        PluginInput $input,
        PluginGeometry $geometry,
        RenderSettings $renderSettings,
        PluginServices $services,
    ): ?FrameSet {
        return $this->buildFrameSetUsing(
            fn(): ?RawScreen => $this->loadBitsFor($input, $geometry, $services),
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen, $geometry->width),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderFrame(
                $parsedScreen,
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
     * @param callable(): ?RawScreen $loadBits
     * @param callable(RawScreen): ParsedScreen $parseScreen
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderFrame
     */
    public function buildFrameSetUsing(
        callable $loadBits,
        callable $parseScreen,
        callable $renderFrame,
        RenderSettings $renderSettings,
        PluginServices $services,
        PluginGeometry $geometry,
    ): ?FrameSet
    {
        $rawScreen = $loadBits();
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $services->paletteService->buildColorTable($renderSettings->paletteString);
        $parsedScreen = $parseScreen($rawScreen);
        return $this->frameSetBuilder->build($parsedScreen, $colorTable, $renderFrame, $renderSettings, $geometry);
    }

    public function loadBitsFor(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?RawScreen
    {
        return $this->rawScreenLoader->load($input, $geometry, $services);
    }

    public function parseScreen(RawScreen $rawScreen, int $width): ParsedScreen
    {
        return $this->parsedScreenParser->parse($rawScreen, $width);
    }

    public function parseScreenWithLinearPixels(RawScreen $rawScreen, int $width): ParsedScreen
    {
        return $this->parsedScreenParser->parseWithLinearPixels($rawScreen, $width);
    }

    public function parseScreenWithZxAttributes(RawScreen $rawScreen, int $width): ParsedScreen
    {
        return $this->parsedScreenParser->parseWithZxAttributes($rawScreen, $width);
    }

    public function renderFrame(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $runtime,
    ): GdImage {
        return $this->frameRenderer->render(
            $parsedScreen,
            $colorTable,
            $flashedImage,
            $runtime,
        );
    }
}
