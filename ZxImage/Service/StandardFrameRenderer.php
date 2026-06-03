<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Plugin\Standard\PixelRenderer;

final readonly class StandardFrameRenderer
{
    public function render(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        PluginGeometry $geometry,
    ): GdImage {
        return (new PixelRenderer())->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $geometry->width,
            $geometry->height,
            $geometry->attributeWidth,
            $geometry->attributeHeight,
        );
    }
}
