<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Monochrome\MonochromeLoader;
use ZxImage\Plugin\Monochrome\MonochromeRenderer;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Service\PluginServices;

class Monochrome implements FramePluginInterface
{
    private const int INK_KEY = 15;
    private const int PAPER_KEY = 8;

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
        $this->geometry = (new PluginGeometry())->withRequiredFileSize(6144);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $rawScreen = (new MonochromeLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $pixelsData = (new PixelParser($this->geometry->width))->parse($rawScreen->pixelsBytes);
        $rows = (int)($this->geometry->height / 8);
        $cols = (int)($this->geometry->width / 8);
        $attributes = new AttributeMap(
            array_fill(0, $rows, array_fill(0, $cols, self::INK_KEY)),
            array_fill(0, $rows, array_fill(0, $cols, self::PAPER_KEY)),
            [],
        );
        $parsedScreen = new ParsedScreen($pixelsData, $attributes);
        $image = (new MonochromeRenderer())->render($parsedScreen, $colorTable, $this->geometry);

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
