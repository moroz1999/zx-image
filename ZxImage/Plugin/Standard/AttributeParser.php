<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Standard;

use ZxImage\Dto\AttributeMap;

readonly class AttributeParser
{
    public function __construct(private int $width)
    {
    }

    public function parse(array $bytes, ?\Closure $zxyMapper = null): AttributeMap
    {
        $x = 0;
        $y = 0;
        $inkMap = [];
        $paperMap = [];
        $flashMap = [];
        $columnsPerRow = (int)($this->width / 8);

        foreach ($bytes as $byte) {
            $bright = ($byte >> 6) & 1;
            $zxY = $zxyMapper !== null ? $zxyMapper($y) : $y;
            $inkMap[$zxY][$x] = ($bright << 3) | ($byte & 0x07);
            $paperMap[$zxY][$x] = ($bright << 3) | (($byte >> 3) & 0x07);
            if (($byte >> 7) & 1) {
                $flashMap[$zxY][$x] = true;
            }
            if ($x === $columnsPerRow - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }

        return new AttributeMap($inkMap, $paperMap, $flashMap);
    }
}
