<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Atmega;

use ZxImage\Dto\PaletteConfig;

final readonly class AtmegaPaletteParser
{
    /**
     * @param list<int> $paletteBytes
     *
     * @return list<int>
     */
    public function parse(array $paletteBytes, PaletteConfig $config): array
    {
        $levels = [0, 0x55, 0xAA, 0xFF];
        $colors = [];

        foreach ($paletteBytes as $byte) {
            $redLevel = (($byte >> 1) & 1) * 2 + (($byte >> 6) & 1);
            $greenLevel = (($byte >> 4) & 1) * 2 + (($byte >> 7) & 1);
            $blueLevel = ($byte & 1) * 2 + (($byte >> 5) & 1);

            $redValue = $levels[$redLevel];
            $greenValue = $levels[$greenLevel];
            $blueValue = $levels[$blueLevel];

            $red = (int)round(
                ($redValue * $config->r11 + $greenValue * $config->r12 + $blueValue * $config->r13) / 0xFF
            );
            $green = (int)round(
                ($redValue * $config->r21 + $greenValue * $config->r22 + $blueValue * $config->r23) / 0xFF
            );
            $blue = (int)round(
                ($redValue * $config->r31 + $greenValue * $config->r32 + $blueValue * $config->r33) / 0xFF
            );

            $colors[] = $red * 0x010000 + $green * 0x0100 + $blue;
        }
        return $colors;
    }
}
