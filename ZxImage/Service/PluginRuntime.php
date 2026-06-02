<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Dto\RenderGeometry;
use ZxImage\Dto\RenderSettings;

final class PluginRuntime
{
    public RenderSettings $renderSettings;

    public int $width = 256;
    public int $height = 192;
    public int $attributeWidth = 8;
    public int $attributeHeight = 8;
    public int $borderWidth = 32;
    public int $borderHeight = 24;
    public bool $usesBorder = true;
    public ?int $requiredFileSize = null;

    public function __construct(
        public ?string $sourceFilePath = null,
        public ?string $sourceFileContents = null,
        public PluginServices $services = new PluginServices(),
        ?RenderSettings $renderSettings = null,
    ) {
        $this->renderSettings = $renderSettings ?? new RenderSettings();
    }

    public function applyRenderSettings(RenderSettings $renderSettings): void
    {
        $this->renderSettings = $renderSettings;
    }

    public function overrideBorder(?int $border): void
    {
        $this->renderSettings = $this->renderSettings->withBorder($border);
    }

    public function getRenderGeometry(): RenderGeometry
    {
        return new RenderGeometry(
            $this->width,
            $this->height,
            $this->borderWidth,
            $this->borderHeight,
            $this->usesBorder,
        );
    }
}
