<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\IndexedPaletteEntry;
use ZxImage\Dto\PaletteConfig;

final readonly class IndexedScreenRenderer
{
    /**
     * @param int[] $pixelsBytes
     * @param IndexedPaletteEntry[] $paletteEntries
     */
    public function renderFrame(
        array $pixelsBytes,
        array $paletteEntries,
        ColorTable $colorTable,
        int $width,
        int $height,
    ): GdImage {
        $colors = $this->parsePalette($paletteEntries, $colorTable->config);
        $pixelsData = $this->parseLinearPixels($pixelsBytes, $width);

        $image = imagecreatetruecolor($width, $height);
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                imagesetpixel($image, $x, $y, $colors[$pixel]);
            }
        }

        return $image;
    }

    /**
     * @param int[] $pixelsBytes
     * @return int[][]
     */
    private function parseLinearPixels(array $pixelsBytes, int $width): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($pixelsBytes as $byte) {
            $pixelsData[$y][$x] = $byte;
            $x++;
            if ($x >= $width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }

    /**
     * @param IndexedPaletteEntry[] $paletteEntries
     * @return int[]
     */
    private function parsePalette(array $paletteEntries, PaletteConfig $config): array
    {
        $colors = [];
        foreach ($paletteEntries as $entry) {
            $r = $this->rgb3ToRgb8(($entry->byte1 >> 5) & 0x07);
            $g = $this->rgb3ToRgb8(($entry->byte1 >> 2) & 0x07);
            $b = $this->rgb3ToRgb8((($entry->byte1 & 0x03) << 1) | ($entry->byte2 & 0x01));

            $red = (int)round(($r * $config->r11 + $g * $config->r12 + $b * $config->r13) / 0xFF);
            $green = (int)round(($r * $config->r21 + $g * $config->r22 + $b * $config->r23) / 0xFF);
            $blue = (int)round(($r * $config->r31 + $g * $config->r32 + $b * $config->r33) / 0xFF);

            $colors[] = $red * 0x010000 + $green * 0x0100 + $blue;
        }
        return $colors;
    }

    private function rgb3ToRgb8(int $value): int
    {
        return match ($value) {
            0 => 0,
            1 => 36,
            2 => 73,
            3 => 109,
            4 => 146,
            5 => 182,
            6 => 219,
            7 => 255,
        };
    }
}
