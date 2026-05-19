<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sxg;

final readonly class SxgPaletteParser
{
    private const array LEVEL_TABLE = [
        0 => 0,
        1 => 10,
        2 => 21,
        3 => 31,
        4 => 42,
        5 => 53,
        6 => 63,
        7 => 74,
        8 => 85,
        9 => 95,
        10 => 106,
        11 => 117,
        12 => 127,
        13 => 138,
        14 => 149,
        15 => 159,
        16 => 170,
        17 => 181,
        18 => 191,
        19 => 202,
        20 => 213,
        21 => 223,
        22 => 234,
        23 => 245,
        24 => 255,
    ];

    /**
     * @param int[] $words
     * @return int[]
     */
    public function parse(array $words): array
    {
        $colors = [];
        foreach ($words as $word) {
            if (($word >> 15) === 0) {
                $r = self::LEVEL_TABLE[($word >> 10) & 0x1F] ?? reset(self::LEVEL_TABLE);
                $g = self::LEVEL_TABLE[($word >> 5) & 0x1F] ?? reset(self::LEVEL_TABLE);
                $b = self::LEVEL_TABLE[$word & 0x1F] ?? reset(self::LEVEL_TABLE);
            } else {
                $r = (($word >> 10) & 0x1F) << 3;
                $g = (($word >> 5) & 0x1F) << 3;
                $b = ($word & 0x1F) << 3;
            }
            $colors[] = $r * 0x010000 + $g * 0x0100 + $b;
        }
        return $colors;
    }
}
