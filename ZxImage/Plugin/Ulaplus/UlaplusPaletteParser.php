<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Ulaplus;

use ZxImage\Dto\PaletteConfig;

final readonly class UlaplusPaletteParser
{
    /**
     * @param array<int, int> $bytes
     *
     * @return list<int>
     */
    public function parse(array $bytes, PaletteConfig $config): array
    {
        $paletteData = [];

        foreach ($bytes as $byte) {
            $green = ($byte >> 5) & 0x07;
            $red = ($byte >> 2) & 0x07;
            $blue = $byte & 0x03;

            $redValue = $red * 32;
            $greenValue = $green * 32;
            $blueValue = $blue * 64;

            $redChannel = (int)round(
                ($redValue * $config->r11 + $greenValue * $config->r12 + $blueValue * $config->r13) / 0xFF
            );
            $greenChannel = (int)round(
                ($redValue * $config->r21 + $greenValue * $config->r22 + $blueValue * $config->r23) / 0xFF
            );
            $blueChannel = (int)round(
                ($redValue * $config->r31 + $greenValue * $config->r32 + $blueValue * $config->r33) / 0xFF
            );

            $paletteData[] = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }
        return $paletteData;
    }
}
