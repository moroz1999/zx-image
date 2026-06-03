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
use ZxImage\Enum\GigascreenMode;

final readonly class GigascreenFrameSetBuilder
{
    /**
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderFrame
     * @param callable(ParsedScreen, ParsedScreen, ColorTable, bool): GdImage $renderMergedFrame
     */
    public function build(
        ParsedScreen $firstScreen,
        ParsedScreen $secondScreen,
        ColorTable $colorTable,
        callable $renderFrame,
        callable $renderMergedFrame,
        RenderSettings $renderSettings,
        PluginGeometry $geometry,
    ): FrameSet {
        $gigascreenMode = GigascreenMode::tryFrom($renderSettings->gigascreenMode) ?? GigascreenMode::Mix;
        if ($gigascreenMode->usesFlickerFrames()) {
            return $this->buildFlickerFrameSet(
                $firstScreen,
                $secondScreen,
                $colorTable,
                $renderSettings,
                $geometry,
                $renderFrame,
                $gigascreenMode,
            );
        }

        return $this->buildMixedFrameSet(
            $firstScreen,
            $secondScreen,
            $colorTable,
            $renderSettings,
            $geometry,
            $renderMergedFrame,
        );
    }

    /**
     * @param callable(ParsedScreen, ColorTable, bool): GdImage $renderFrame
     */
    private function buildFlickerFrameSet(
        ParsedScreen $firstScreen,
        ParsedScreen $secondScreen,
        ColorTable $colorTable,
        RenderSettings $renderSettings,
        PluginGeometry $geometry,
        callable $renderFrame,
        GigascreenMode $gigascreenMode,
    ): FrameSet {
        $hasFlash = $this->hasFlash($firstScreen, $secondScreen);
        $frames = [];

        if ($hasFlash) {
            for ($frameIndex = 0; $frameIndex < 32; $frameIndex++) {
                $flashedImage = $frameIndex >= 16;
                $screen = ($frameIndex & 1) === 1 ? $firstScreen : $secondScreen;
                $image = $renderFrame($screen, $colorTable, $flashedImage);
                $frames[] = new Frame($image, 2, $renderSettings);
            }
        } else {
            $frames[] = new Frame($renderFrame($firstScreen, $colorTable, false), 2, $renderSettings);
            $frames[] = new Frame($renderFrame($secondScreen, $colorTable, false), 2, $renderSettings);
        }

        return new FrameSet(
            $frames,
            $renderSettings,
            $geometry->toRenderGeometry(),
            $colorTable,
            $gigascreenMode->interlaceLineHeight(),
        );
    }

    /**
     * @param callable(ParsedScreen, ParsedScreen, ColorTable, bool): GdImage $renderMergedFrame
     */
    private function buildMixedFrameSet(
        ParsedScreen $firstScreen,
        ParsedScreen $secondScreen,
        ColorTable $colorTable,
        RenderSettings $renderSettings,
        PluginGeometry $geometry,
        callable $renderMergedFrame,
    ): FrameSet {
        if ($this->hasFlash($firstScreen, $secondScreen)) {
            return new FrameSet(
                [
                    new Frame($renderMergedFrame($firstScreen, $secondScreen, $colorTable, false), 32, $renderSettings),
                    new Frame($renderMergedFrame($firstScreen, $secondScreen, $colorTable, true), 32, $renderSettings),
                ],
                $renderSettings,
                $geometry->toRenderGeometry(),
                $colorTable,
            );
        }

        return new FrameSet(
            [new Frame($renderMergedFrame($firstScreen, $secondScreen, $colorTable, false), 0, $renderSettings)],
            $renderSettings,
            $geometry->toRenderGeometry(),
            $colorTable,
        );
    }

    private function hasFlash(ParsedScreen $firstScreen, ParsedScreen $secondScreen): bool
    {
        return count($firstScreen->attributes->flashMap) > 0
            || count($secondScreen->attributes->flashMap) > 0;
    }
}
