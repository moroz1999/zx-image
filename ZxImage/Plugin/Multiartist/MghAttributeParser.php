<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Multiartist;

use ZxImage\Dto\AttributeMap;
use ZxImage\Plugin\Standard\AttributeParser;

final readonly class MghAttributeParser
{
    private const int MGH_MODE_1 = 1;

    /**
     * @param int[] $innerBytes
     * @param int[] $outerBytes
     */
    public function parse(int $mghMode, array $innerBytes, array $outerBytes, int $width): AttributeMap
    {
        if ($mghMode === self::MGH_MODE_1) {
            return $this->buildMgh1Map($innerBytes, $outerBytes);
        }
        return (new AttributeParser($width))->parse($innerBytes);
    }

    /**
     * @param int[] $innerBytes
     * @param int[] $outerBytes
     */
    private function buildMgh1Map(array $innerBytes, array $outerBytes): AttributeMap
    {
        $x = 8;
        $y = 0;
        $inkMap = [];
        $paperMap = [];
        $flashMap = [];

        foreach ($innerBytes as $byte) {
            $bright = ($byte >> 6) & 1;
            $inkMap[$y][$x] = ($bright << 3) | ($byte & 0x07);
            $paperMap[$y][$x] = ($bright << 3) | (($byte >> 3) & 0x07);
            if (($byte >> 7) & 1) {
                $flashMap[$y][$x] = true;
            }
            if ($x === 23) {
                $x = 8;
                $y++;
            } else {
                $x++;
            }
        }

        $x = 0;
        $y = 0;
        foreach ($outerBytes as $byte) {
            $bright = ($byte >> 6) & 1;
            $inkKey = ($bright << 3) | ($byte & 0x07);
            $paperKey = ($bright << 3) | (($byte >> 3) & 0x07);
            $isFlash = ($byte >> 7) & 1;

            for ($i = 0; $i < 8; $i++) {
                $inkMap[$y + $i][$x] = $inkKey;
                $paperMap[$y + $i][$x] = $paperKey;
                if ($isFlash) {
                    $flashMap[$y + $i][$x] = true;
                }
            }

            if ($x === 7) {
                $x = 24;
            } elseif ($x === 31) {
                $x = 0;
                $y += 8;
            } else {
                $x++;
            }
        }

        return new AttributeMap($inkMap, $paperMap, $flashMap);
    }
}
