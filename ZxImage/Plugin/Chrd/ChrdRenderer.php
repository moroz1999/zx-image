<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Chrd;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Plugin\Standard\PixelRenderer;

final readonly class ChrdRenderer
{
    public function __construct(
        private PixelRenderer $pixelRenderer = new PixelRenderer(),
    ) {
    }

    public function render(
        ParsedScreen $screen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $geometry,
    ): GdImage {
        return $this->pixelRenderer->render(
            $screen,
            $flashedImage,
            $colorTable->colors,
            $geometry->width,
            $geometry->height,
            $geometry->attributeWidth,
            $geometry->attributeHeight,
        );
    }
}
