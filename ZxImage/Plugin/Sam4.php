<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Service\PluginServices;
use ZxImage\Service\SamCoupeScreenRenderer;

class Sam4 implements FramePluginInterface
{
    private const int PALETTE_LENGTH = 16;
    private const int BITS_PER_PIXEL = 4;

    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;
    private SamCoupeScreenRenderer $renderer;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry(width: 256, height: 192);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
        $this->renderer = new SamCoupeScreenRenderer();
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

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

        $pixelByteCount = (int)($this->geometry->width * $this->geometry->height / (8 / self::BITS_PER_PIXEL));
        $pixelsBytes = $reader->readBytes($pixelByteCount);
        $paletteBytes = $reader->readBytes(self::PALETTE_LENGTH);

        $image = $this->renderer->renderFrame(
            $pixelsBytes,
            $paletteBytes,
            self::BITS_PER_PIXEL,
            false,
            false,
            $colorTable,
            $this->geometry->width,
            $this->geometry->height,
        );

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
