<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Timexhr;

use ZxImage\Dto\AttributeMap;

final readonly class TimexhrAttributeBuilder
{
    private const array COLOR_PAIRS = [
        [8, 15],
        [9, 14],
        [10, 13],
        [11, 12],
        [12, 11],
        [13, 10],
        [14, 9],
        [15, 8],
    ];

    public function build(int $byte, int $width, int $height): AttributeMap
    {
        $colorCode = ($byte >> 3) & 0x07;
        [$inkKey, $paperKey] = self::COLOR_PAIRS[$colorCode];

        $rows = (int)($height / 8);
        $cols = (int)($width / 8);
        return new AttributeMap(
            array_fill(0, $rows, array_fill(0, $cols, $inkKey)),
            array_fill(0, $rows, array_fill(0, $cols, $paperKey)),
            [],
        );
    }
}
