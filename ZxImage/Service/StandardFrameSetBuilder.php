<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\RenderSettings;

final readonly class StandardFrameSetBuilder
{
    /**
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderFrame
     */
    public function build(
        ParsedScreen $parsedScreen,
        ColorTable $colorTable,
        callable $renderFrame,
        RenderSettings $renderSettings,
        PluginGeometry $geometry,
    ): FrameSet {
        if (count($parsedScreen->attributes->flashMap) > 0) {
            return new FrameSet(
                [
                    new Frame($renderFrame($parsedScreen, $colorTable, false), 32),
                    new Frame($renderFrame($parsedScreen, $colorTable, true), 32),
                ],
                $renderSettings,
                $geometry->toRenderGeometry(),
                $colorTable,
            );
        }

        return new FrameSet(
            [new Frame($renderFrame($parsedScreen, $colorTable, false))],
            $renderSettings,
            $geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
