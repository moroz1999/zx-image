<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sca;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Plugin\Standard\PixelRenderer;

final readonly class ScaRenderer
{
    public function __construct(
        private PixelRenderer $pixelRenderer = new PixelRenderer(),
    ) {
    }

    public function render(ParsedScreen $parsedScreen, ColorTable $colorTable, PluginGeometry $geometry): GdImage
    {
        return $this->pixelRenderer->render(
            $parsedScreen,
            false,
            $colorTable->colors,
            $geometry->width,
            $geometry->height,
            $geometry->attributeWidth,
            $geometry->attributeHeight,
        );
    }
}
