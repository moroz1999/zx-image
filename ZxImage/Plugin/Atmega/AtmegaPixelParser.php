<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Atmega;

final readonly class AtmegaPixelParser
{
    private const int PAGE_SIZE = 8000;

    /**
     * @param list<int> $pixelsArray
     *
     * @return array<int, array<int, int>>
     */
    public function parse(array $pixelsArray, int $width): array
    {
        $x = 0;
        $y = 0;
        $length = 0;
        $block = 0;
        $pixelsData = [];

        foreach ($pixelsArray as $byte) {
            $length++;
            $pixelsData[$y][$x * 2] = ((($byte & 0x40) >> 3) | ($byte & 0x07));
            $pixelsData[$y][$x * 2 + 1] = ((($byte & 0x80) >> 4) | (($byte >> 3) & 0x07));

            $x = $x + 4;

            if ($x >= $width / 2) {
                $x = (int)floor($length / self::PAGE_SIZE);
                if ($block !== $x) {
                    $block = $x;
                    $y = 0;
                } else {
                    $y++;
                }
            }
        }
        return $pixelsData;
    }
}
