<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sxg;

final readonly class SxgPixelParser
{
    public const int FORMAT_16 = 1;

    /**
     * @param list<int> $pixelsBytes
     *
     * @return array<int, array<int, int>>
     */
    public function parse(array $pixelsBytes, int $format, int $width): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];

        if ($format === self::FORMAT_16) {
            foreach ($pixelsBytes as $byte) {
                $pixelsData[$y][$x] = ($byte >> 4) & 0x0F;
                $x++;
                $pixelsData[$y][$x] = $byte & 0x0F;
                $x++;
                if ($x >= $width) {
                    $x = 0;
                    $y++;
                }
            }
        } else {
            foreach ($pixelsBytes as $pixel) {
                $pixelsData[$y][$x] = $pixel;
                $x++;
                if ($x >= $width) {
                    $x = 0;
                    $y++;
                }
            }
        }
        return $pixelsData;
    }
}
