<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use RuntimeException;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\IndexedPaletteEntry;
use ZxImage\Dto\PaletteConfig;

final readonly class IndexedScreenRenderer
{
    /**
     * @param array<int, int>                 $pixelsBytes
     * @param array<int, IndexedPaletteEntry> $paletteEntries
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
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                imagesetpixel($image, $x, $y, $colors[$pixel]);
            }
        }

        return $image;
    }

    /**
     * @param array<int, int> $pixelsBytes
     *
     * @return array<int, array<int, int>>
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
     * @param array<int, IndexedPaletteEntry> $paletteEntries
     *
     * @return list<int>
     */
    private function parsePalette(array $paletteEntries, PaletteConfig $config): array
    {
        $colors = [];
        foreach ($paletteEntries as $entry) {
            $redValue = $this->rgb3ToRgb8(($entry->byte1 >> 5) & 0x07);
            $greenValue = $this->rgb3ToRgb8(($entry->byte1 >> 2) & 0x07);
            $blueValue = $this->rgb3ToRgb8((($entry->byte1 & 0x03) << 1) | ($entry->byte2 & 0x01));

            $red = (int)round(
                ($redValue * $config->r11 + $greenValue * $config->r12 + $blueValue * $config->r13) / 0xFF
            );
            $green = (int)round(
                ($redValue * $config->r21 + $greenValue * $config->r22 + $blueValue * $config->r23) / 0xFF
            );
            $blue = (int)round(
                ($redValue * $config->r31 + $greenValue * $config->r32 + $blueValue * $config->r33) / 0xFF
            );

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
