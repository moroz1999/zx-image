<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Timexhr\TimexhrAttributeBuilder;
use ZxImage\Plugin\Timexhr\TimexhrPixelRenderer;
use ZxImage\Service\PluginServices;

class Timexhr implements FramePluginInterface
{
    private const int REQUIRED_FILE_SIZE = 12289;
    private const int WIDTH = 512;
    private const int HEIGHT = 384;
    private const int PLANE_SIZE = 6144;

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
        $reader = $this->services->fileLoader->openSource(
            $this->input->sourceFilePath,
            $this->input->sourceFileContents,
            $this->geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $pixelsArray1 = $reader->readBytes(self::PLANE_SIZE);
        $pixelsArray2 = $reader->readBytes(self::PLANE_SIZE);
        $attributeByte = $reader->readByte() ?? 0;

        $pixelsArray = [];
        for ($i = 0; $i < self::PLANE_SIZE; $i++) {
            $pixelsArray[] = $pixelsArray1[$i];
            $pixelsArray[] = $pixelsArray2[$i];
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);
        $attributes = (new TimexhrAttributeBuilder())->build(
            $attributeByte,
            $this->geometry->width,
            $this->geometry->height,
        );
        $pixelsData = (new PixelParser($this->geometry->width))->parse($pixelsArray);
        $parsedScreen = new ParsedScreen($pixelsData, $attributes);

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
