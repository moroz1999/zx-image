<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Ulaplus\UlaplusLoader;
use ZxImage\Plugin\Ulaplus\UlaplusPixelRenderer;
use ZxImage\Plugin\Ulaplus\UlaplusScreenParser;
use ZxImage\Service\PluginServices;

class Ulaplus implements FramePluginInterface
{
    private const int REQUIRED_FILE_SIZE = 6976;

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
        $this->geometry = new PluginGeometry(requiredFileSize: self::REQUIRED_FILE_SIZE);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $rawScreen = (new UlaplusLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $parsedScreen = (new UlaplusScreenParser())->parse($rawScreen, $this->geometry->width, $colorTable->config);

        $image = (new UlaplusPixelRenderer())->render(
            $parsedScreen,
            $this->geometry->width,
            $this->geometry->height,
            $this->geometry->attributeWidth,
            $this->geometry->attributeHeight,
        );

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
