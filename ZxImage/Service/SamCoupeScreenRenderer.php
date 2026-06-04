<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use RuntimeException;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\PaletteConfig;

final readonly class SamCoupeScreenRenderer
{
    private const int BRIGHTNESS_MULTIPLIER = 36;

    /**
     * @param array<int, int> $pixelsBytes
     * @param array<int, int> $paletteBytes
     */
    public function renderFrame(
        array $pixelsBytes,
        array $paletteBytes,
        int $bitsPerPixel,
        bool $doubleRows,
        bool $swapMode3Colors,
        ColorTable $colorTable,
        int $width,
        int $height,
    ): GdImage {
        $colors = $this->parsePalette($paletteBytes, $colorTable->config);
        $pixelsData = $this->parsePixels($pixelsBytes, $bitsPerPixel, $width);

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $colorIndex) {
                if ($swapMode3Colors === true) {
                    $colorIndex = $this->swapMode3Color($colorIndex);
                }

                $color = $colors[$colorIndex];
                if ($doubleRows === true) {
                    imagesetpixel($image, $x, $y * 2, $color);
                    imagesetpixel($image, $x, $y * 2 + 1, $color);
                } else {
                    imagesetpixel($image, $x, $y, $color);
                }
            }
        }

        return $image;
    }

    /**
     * @param array<int, int> $pixelsBytes
     *
     * @return array<int, array<int, int>>
     */
    private function parsePixels(array $pixelsBytes, int $bitsPerPixel, int $width): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($pixelsBytes as $byte) {
            $colorIndexes = $bitsPerPixel === 2
                ? [($byte >> 6) & 0x03, ($byte >> 4) & 0x03, ($byte >> 2) & 0x03, $byte & 0x03]
                : [($byte >> 4) & 0x0F, $byte & 0x0F];

            foreach ($colorIndexes as $colorIndex) {
                $pixelsData[$y][$x] = $colorIndex;
                $x++;
                if ($x >= $width) {
                    $x = 0;
                    $y++;
                }
            }
        }
        return $pixelsData;
    }

    /**
     * @param array<int, int> $paletteBytes
     *
     * @return list<int>
     */
    private function parsePalette(array $paletteBytes, PaletteConfig $config): array
    {
        $colors = [];
        foreach ($paletteBytes as $byte) {
            $bright = ($byte >> 3) & 1;
            $redValue = ((($byte >> 5) & 1) * 4 + (($byte >> 1) & 1) * 2 + $bright) * self::BRIGHTNESS_MULTIPLIER;
            $greenValue = ((($byte >> 6) & 1) * 4 + (($byte >> 2) & 1) * 2 + $bright) * self::BRIGHTNESS_MULTIPLIER;
            $blueValue = ((($byte >> 4) & 1) * 4 + ($byte & 1) * 2 + $bright) * self::BRIGHTNESS_MULTIPLIER;

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

    private function swapMode3Color(int $colorIndex): int
    {
        if ($colorIndex === 1) {
            return 2;
        }

        if ($colorIndex === 2) {
            return 1;
        }

        return $colorIndex;
    }
}
