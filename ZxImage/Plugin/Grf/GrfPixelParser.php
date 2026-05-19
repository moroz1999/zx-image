<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Grf;

final readonly class GrfPixelParser
{
    /**
     * @param int[] $pixelsArray
     * @param int[] $attributesArray
     * @return int[][]
     */
    public function parse(array $pixelsArray, array $attributesArray, int $width): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];

        foreach ($pixelsArray as $key => $pixelByte) {
            $attrByte = $attributesArray[$key];
            $ink = (($attrByte >> 3) & 0x08) | ($attrByte & 0x07);
            $paper = (($attrByte >> 4) & 0x08) | (($attrByte >> 3) & 0x07);
            for ($bit = 0; $bit < 8; $bit++) {
                $pixelsData[$y][$x] = ($pixelByte & (0x80 >> $bit)) ? $ink : $paper;
                $x++;
            }
            if ($x >= $width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }
}
