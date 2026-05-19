<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Ulaplus;

use ZxImage\Dto\PaletteConfig;

final readonly class UlaplusPaletteParser
{
    public function parse(array $bytes, PaletteConfig $config): array
    {
        $paletteData = [];

        foreach ($bytes as $byte) {
            $g = ($byte >> 5) & 0x07;
            $r = ($byte >> 2) & 0x07;
            $b = $byte & 0x03;

            $rValue = $r * 32;
            $gValue = $g * 32;
            $bValue = $b * 64;

            $redChannel = (int)round(
                ($rValue * $config->r11 + $gValue * $config->r12 + $bValue * $config->r13) / 0xFF
            );
            $greenChannel = (int)round(
                ($rValue * $config->r21 + $gValue * $config->r22 + $bValue * $config->r23) / 0xFF
            );
            $blueChannel = (int)round(
                ($rValue * $config->r31 + $gValue * $config->r32 + $bValue * $config->r33) / 0xFF
            );

            $paletteData[] = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }
        return $paletteData;
    }
}
