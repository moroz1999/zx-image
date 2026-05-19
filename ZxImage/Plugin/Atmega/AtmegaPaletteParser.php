<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Atmega;

use ZxImage\Dto\PaletteConfig;

final readonly class AtmegaPaletteParser
{
    /**
     * @param int[] $paletteBytes
     * @return int[]
     */
    public function parse(array $paletteBytes, PaletteConfig $config): array
    {
        $levels = [0, 0x55, 0xAA, 0xFF];
        $colors = [];

        foreach ($paletteBytes as $byte) {
            $rValue = (($byte >> 1) & 1) * 2 + (($byte >> 6) & 1);
            $gValue = (($byte >> 4) & 1) * 2 + (($byte >> 7) & 1);
            $bValue = ($byte & 1) * 2 + (($byte >> 5) & 1);

            $r = $levels[$rValue];
            $g = $levels[$gValue];
            $b = $levels[$bValue];

            $red = (int)round(($r * $config->r11 + $g * $config->r12 + $b * $config->r13) / 0xFF);
            $green = (int)round(($r * $config->r21 + $g * $config->r22 + $b * $config->r23) / 0xFF);
            $blue = (int)round(($r * $config->r31 + $g * $config->r32 + $b * $config->r33) / 0xFF);

            $colors[] = $red * 0x010000 + $green * 0x0100 + $blue;
        }
        return $colors;
    }
}
