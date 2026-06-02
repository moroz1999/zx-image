<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderGeometry;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Multiartist\MghAttributeParser;
use ZxImage\Plugin\Multiartist\MghBorders;
use ZxImage\Plugin\Multiartist\MghDimensions;
use ZxImage\Plugin\Multiartist\MghRenderer;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginServices;

class Multiartist implements FramePluginInterface
{
    private const int MGH_MODE_1 = 1;
    private const int MGH_MODE_2 = 2;
    private const int MGH_MODE_4 = 4;
    private const int MGH_MODE_8 = 8;
    private const int VERSION_OFFSET = 3;
    private const int MODE_OFFSET = 4;
    private const int FIRST_BORDER_OFFSET = 5;
    private const int SECOND_BORDER_OFFSET = 6;

    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry();
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $reader = $this->services->fileLoader->openSource(
            $this->input->sourceFilePath,
            $this->input->sourceFileContents,
            null,
        );
        if ($reader === null) {
            return null;
        }

        $header = $reader->readString(256);
        if ($header === null) {
            return null;
        }

        $signature = substr($header, 0, 3);
        $version = ord($header[self::VERSION_OFFSET]);
        if ($signature !== 'MGH' || $version !== 1) {
            return null;
        }

        $mghMode = ord($header[self::MODE_OFFSET]);
        $borders = $this->parseBorders($header);

        $dimensions = $this->getMghDimensions($mghMode);
        $this->geometry = $this->geometry->withAttributeHeight($dimensions->attributeHeight);

        $pixelsBytes1 = $reader->readBytes(6144);
        $pixelsBytes2 = $reader->readBytes(6144);
        $attributesBytes1 = $reader->readBytes($dimensions->attributesLength);
        $attributesBytes2 = $reader->readBytes($dimensions->attributesLength);

        $outerAttributesBytes1 = [];
        $outerAttributesBytes2 = [];
        if ($mghMode === self::MGH_MODE_1) {
            $outerAttributesBytes1 = $reader->readBytes($dimensions->outerAttributesLength);
            $outerAttributesBytes2 = $reader->readBytes($dimensions->outerAttributesLength);
        }

        $attrParser = new MghAttributeParser();
        $pixelParser = new PixelParser($this->geometry->width);
        $screen1 = new ParsedScreen(
            $pixelParser->parse($pixelsBytes1),
            $attrParser->parse($mghMode, $attributesBytes1, $outerAttributesBytes1, $this->geometry->width),
        );
        $screen2 = new ParsedScreen(
            $pixelParser->parse($pixelsBytes2),
            $attrParser->parse($mghMode, $attributesBytes2, $outerAttributesBytes2, $this->geometry->width),
        );

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        return $this->buildResult($screen1, $screen2, $borders, $colorTable);
    }

    private function parseBorders(string $header): MghBorders
    {
        if ($this->renderSettings->border !== null) {
            return new MghBorders(
                ord($header[self::FIRST_BORDER_OFFSET]),
                ord($header[self::SECOND_BORDER_OFFSET]),
            );
        }
        return new MghBorders(null, null);
    }

    private function getMghDimensions(int $mghMode): MghDimensions
    {
        return match ($mghMode) {
            self::MGH_MODE_1 => new MghDimensions(1, 3072, 384),
            self::MGH_MODE_2 => new MghDimensions(2, 3072, 0),
            self::MGH_MODE_4 => new MghDimensions(4, 1536, 0),
            default => new MghDimensions(8, 768, 0),
        };
    }

    private function buildResult(ParsedScreen $screen1, ParsedScreen $screen2, MghBorders $borders, ColorTable $colorTable): FrameSet
    {
        $pipeline = new GigascreenPipeline();
        $renderer = new MghRenderer();

        $renderSingle1 = fn(ParsedScreen $screen, ColorTable $ct, bool $flashedImage) => $renderer->renderSingle(
            $screen,
            $screen1,
            $borders,
            $ct,
            $flashedImage,
            $this->geometry,
            $this->services,
        );

        $renderMerged = fn(ParsedScreen $s1, ParsedScreen $s2, ColorTable $ct, bool $flashedImage) => $renderer->renderMerged(
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
