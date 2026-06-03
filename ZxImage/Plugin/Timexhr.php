<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Timexhr\TimexhrLoader;
use ZxImage\Plugin\Timexhr\TimexhrPixelRenderer;
use ZxImage\Plugin\Timexhr\TimexhrScreenParser;
use ZxImage\Service\PluginServices;

class Timexhr implements FramePluginInterface
{
    private const int REQUIRED_FILE_SIZE = 12289;
    private const int WIDTH = 512;
    private const int HEIGHT = 384;

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
        $this->geometry = (new PluginGeometry(requiredFileSize: self::REQUIRED_FILE_SIZE))
            ->withDimensions(self::WIDTH, self::HEIGHT);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $timexhrData = (new TimexhrLoader())->loadFrom($this->input, $this->geometry, $this->services);
        if ($timexhrData === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $parsedScreen = (new TimexhrScreenParser())->parse(
            $timexhrData->pixelsBytes,
            $timexhrData->attributeByte,
            $this->geometry->width,
            $this->geometry->height,
        );

        $renderSettings = $this->renderSettings;
        $paperColor = $parsedScreen->attributes->paperMap[0][0];
        if ($this->renderSettings->border !== null) {
            $renderSettings = $renderSettings->withBorder($paperColor);
        }

        $image = (new TimexhrPixelRenderer())->render(
            $parsedScreen,
            $colorTable,
            $this->geometry->width,
            $this->geometry->height,
        );

        return new FrameSet(
            [new Frame($image)],
            $renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
