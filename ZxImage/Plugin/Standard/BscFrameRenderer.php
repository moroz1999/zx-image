<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Standard;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;

final readonly class BscFrameRenderer
{
    public function __construct(
        private PixelRenderer $pixelRenderer = new PixelRenderer(),
        private BscBorderRenderer $borderRenderer = new BscBorderRenderer(),
    ) {
    }

    public function render(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        bool $flashedImage,
        ?int $border,
        PluginGeometry $geometry,
    ): GdImage {
        $image = $this->pixelRenderer->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $geometry->width,
            $geometry->height,
            $geometry->attributeWidth,
            $geometry->attributeHeight,
        );

        return $this->borderRenderer->render(
            $image,
            $parsedScreen,
            $colorTable,
            $border,
            $geometry->width,
            $geometry->height,
            $geometry->borderWidth,
            $geometry->borderHeight,
        );
    }
}
