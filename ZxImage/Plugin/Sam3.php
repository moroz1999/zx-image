<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\SamCoupe\SamCoupeScreenLoader;
use ZxImage\Service\PluginServices;
use ZxImage\Service\SamCoupeScreenRenderer;

final class Sam3 implements FramePluginInterface
{
    private const int PALETTE_LENGTH = 4;
    private const int BITS_PER_PIXEL = 2;

    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;
    private SamCoupeScreenRenderer $renderer;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry(width: 512, height: 384);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
        $this->renderer = new SamCoupeScreenRenderer();
    }

    #[Override]
    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    #[Override]
    public function convertFrames(): ?FrameSet
    {
        $screenData = (new SamCoupeScreenLoader())->loadFrom(
            $this->input,
            $this->geometry,
            $this->services,
            self::BITS_PER_PIXEL,
            self::PALETTE_LENGTH,
            true,
        );
        if ($screenData === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

        $image = $this->renderer->renderFrame(
            $screenData->pixelsBytes,
            $screenData->paletteBytes,
            self::BITS_PER_PIXEL,
            true,
            true,
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
