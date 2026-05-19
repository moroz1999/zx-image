<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Grf;

final readonly class GrfPaletteParser
{
    /**
     * @param int[] $paletteBytes
     * @return int[]
     */
    public function parse(array $paletteBytes): array
    {
        $colors = [];
        foreach ($paletteBytes as $byte) {
            $green = (($byte >> 5) & 0x07) * 36;
            $red = (($byte >> 2) & 0x07) * 36;
            $blue = ($byte & 0x03) * 85;
            $colors[] = $red * 0x010000 + $green * 0x0100 + $blue;
        }
        return $colors;
    }
}
