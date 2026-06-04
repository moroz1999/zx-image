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
use ZxImage\Dto\RenderGeometry;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Multiartist\MghBorders;
use ZxImage\Plugin\Multiartist\MghLoader;
use ZxImage\Plugin\Multiartist\MghRenderer;
use ZxImage\Plugin\Multiartist\MghScreenParser;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginServices;

final class Multiartist implements FramePluginInterface
{
    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry();
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    #[Override]
    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    #[Override]
    public function convertFrames(): ?FrameSet
    {
        $mghData = (new MghLoader())->loadFrom(
            $this->input,
            $this->services,
            $this->renderSettings->border !== null,
        );
        if ($mghData === null) {
            return null;
        }

        $this->geometry = $this->geometry->withAttributeHeight($mghData->dimensions->attributeHeight);
        $parsedScreens = (new MghScreenParser())->parse($mghData, $this->geometry->width);
        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        return $this->buildResult($parsedScreens->first, $parsedScreens->second, $mghData->borders, $colorTable);
    }

    private function buildResult(ParsedScreen $screen1, ParsedScreen $screen2, MghBorders $borders, ColorTable $colorTable): FrameSet
    {
        $pipeline = new GigascreenPipeline();
        $renderer = new MghRenderer();

        $renderSingle1 = fn(ParsedScreen $screen, ColorTable $ct, bool $flashedImage): GdImage => $renderer->renderSingle(
            $screen,
            $screen1,
            $borders,
            $ct,
            $flashedImage,
            $this->geometry,
            $this->services,
        );

        $renderMerged = fn(ParsedScreen $s1, ParsedScreen $s2, ColorTable $ct, bool $flashedImage): GdImage => $renderer->renderMerged(
            $s1,
            $s2,
            $borders,
            $ct,
            $flashedImage,
            $this->geometry,
            $this->services,
        );

        $frameSet = $pipeline->buildFrameSetFromParsedScreens(
            $screen1,
            $screen2,
            $colorTable,
            $renderSingle1,
            $renderMerged,
            $this->renderSettings,
            $this->geometry,
        );

        return new FrameSet(
            $frameSet->frames,
            $frameSet->renderSettings,
            $this->getFrameGeometry($borders),
            $frameSet->colorTable,
            $frameSet->interlaceLineHeight,
        );
    }

    private function getFrameGeometry(MghBorders $borders): RenderGeometry
    {
        if ($borders->border1 !== null && $borders->border2 !== null) {
            return new RenderGeometry(320, 240, 0, 0, false);
        }

        return new RenderGeometry($this->geometry->width, $this->geometry->height, 0, 0, false);
    }
}
