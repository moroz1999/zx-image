<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Standard;

use Closure;

final readonly class PixelParser
{
    public function __construct(private int $width)
    {
    }

    /**
     * @param array<int, int>         $bytes
     * @param null|Closure(int): int $zxyMapper
     *
     * @return array<int, array<int, int>>
     */
    public function parse(array $bytes, ?Closure $zxyMapper = null): array
    {
        $x = 0;
        $y = 0;
        $zxY = 0;
        $pixelsData = [];

        foreach ($bytes as $byte) {
            for ($bitPosition = 7; $bitPosition >= 0; $bitPosition--) {
                $pixelsData[$zxY][$x] = ($byte >> $bitPosition) & 1;
                $x++;
                if ($x >= $this->width) {
                    $x = 0;
                    $y++;
                    $zxY = $zxyMapper !== null ? $zxyMapper($y) : $this->calculateZXY($y);
                }
            }
        }

        return $pixelsData;
    }

    private function calculateZXY(int $y): int
    {
        $offset = 0;
        if ($y > 127) {
            $offset = 128;
            $y -= 128;
        } elseif ($y > 63) {
            $offset = 64;
            $y -= 64;
        }
        $rows = (int)($y / 8);
        $rests = $y - $rows * 8;
        return $offset + $rests * 8 + $rows;
    }
}
