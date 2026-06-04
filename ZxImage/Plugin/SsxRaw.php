<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use Override;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\SsxRaw\SsxRawLoader;
use ZxImage\Plugin\SsxRaw\SsxRawRenderer;
use ZxImage\Service\PluginServices;

final class SsxRaw implements FramePluginInterface
{
    private const int REQUIRED_FILE_SIZE = 98304;

    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry(
            width: 512,
            height: 192,
            usesBorder: false,
            requiredFileSize: self::REQUIRED_FILE_SIZE,
        );
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
        $ssxRawData = (new SsxRawLoader())->loadFrom($this->input, self::REQUIRED_FILE_SIZE, $this->services);
        if ($ssxRawData === null) {
            return null;
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

        $image = (new SsxRawRenderer())->render(
            $ssxRawData->pixelsBytes,
            $this->geometry->width,
            $this->geometry->height,
            $colorTable->config,
        );

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
