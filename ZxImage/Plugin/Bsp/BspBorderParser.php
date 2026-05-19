<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Bsp;

final readonly class BspBorderParser
{
    private const int BORDER_HEIGHT_BOTTOM = 48;

    /**
     * @param int[] $data
     * @return array<int, array<int, int>>
     */
    public function parse(array $data, int $width, int $height, int $borderWidth, int $borderHeight): array
    {
        $totalWidth = $width + $borderWidth * 2;
        $totalHeight = $height + $borderHeight + self::BORDER_HEIGHT_BOTTOM;
        $borderData = [];
        $x = 0;
        $y = 0;
        $inCenter = false;

        while ($data !== []) {
            $byte = array_shift($data);
            $colorCode = $byte & 0x07;
            $tacts = $byte >> 3;
            $line = 0;
            $untilEnd = false;

            if ($tacts === 0) {
                $untilEnd = true;
            } elseif ($tacts === 1) {
                $line = array_shift($data) ?? 0;
            } elseif ($tacts === 2) {
                $line = 12;
            } else {
                $line = $tacts + 13;
            }
            $line *= 2;

            while ($untilEnd || $line > 0) {
                $borderData[$y][$x] = $colorCode;
                $x++;

                if ($inCenter && $x === $borderWidth) {
                    $x = $borderWidth + $width;
                    $untilEnd = false;
                }
                if ($x === $totalWidth) {
                    $untilEnd = false;
                    $x = 0;
                    $y++;
                    $inCenter = $y >= $borderHeight && $y < $totalHeight - self::BORDER_HEIGHT_BOTTOM;
                }
                if (!$untilEnd) {
                    $line--;
                }
            }
        }

        return $borderData;
    }
}
