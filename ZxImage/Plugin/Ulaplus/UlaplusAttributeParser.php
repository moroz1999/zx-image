<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Ulaplus;

use ZxImage\Dto\AttributeMap;

final readonly class UlaplusAttributeParser
{
    /**
     * @param array<int, int> $bytes
     */
    public function parse(array $bytes, int $width): AttributeMap
    {
        $x = 0;
        $y = 0;
        $inkMap = [];
        $paperMap = [];
        $columnsPerRow = intdiv($width, 8);

        foreach ($bytes as $byte) {
            $group = ($byte >> 6) & 0x03;
            $inkMap[$y][$x] = $group * 16 + ($byte & 0x07);
            $paperMap[$y][$x] = $group * 16 + (($byte >> 3) & 0x07) + 8;

            if ($x === $columnsPerRow - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }

        return new AttributeMap($inkMap, $paperMap, []);
    }
}
