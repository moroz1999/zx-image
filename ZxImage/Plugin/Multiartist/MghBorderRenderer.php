<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Multiartist;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Service\PluginRuntime;

final readonly class MghBorderRenderer
{
    public function apply(GdImage $center, ?int $border1, ?int $border2, ColorTable $colorTable, PluginRuntime $runtime): GdImage
    {
        if ($border1 !== null && $border2 !== null) {
            $result = imagecreatetruecolor(320, 240);
            $color = $colorTable->gigaColors[($border1 << 4) | $border2];
            imagefill($result, 0, 0, $color);
            imagecopy($result, $center, 32, 24, 0, 0, $runtime->width, $runtime->height);
            return $result;
        }

        return $runtime->imageProcessor->applyBorder(
            $center,
            $border1,
            $colorTable,
            $runtime->width,
            $runtime->height,
            $runtime->borderWidth,
            $runtime->borderHeight,
            $runtime->usesBorder,
        );
    }
}
