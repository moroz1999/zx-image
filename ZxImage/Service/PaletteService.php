<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Dto\ColorTable;
use ZxImage\Dto\PaletteConfig;

final readonly class PaletteService
{
    public function buildColorTable(string $paletteString): ColorTable
    {
        $config = $this->parsePaletteString($paletteString);
        $colors = $this->generateColors($config);
        $gigaColors = $this->generateGigaColors($config);
        return new ColorTable($config, $colors, $gigaColors);
    }

    private function parsePaletteString(string $paletteString): PaletteConfig
    {
        $paletteData = explode(':', $paletteString);
        $baseColors = explode(',', $paletteData[0]);
        $correctionColors = explode(';', $paletteData[1]);
        $redData = explode(',', $correctionColors[0]);
        $greenData = explode(',', $correctionColors[1]);
        $blueData = explode(',', $correctionColors[2]);

        return new PaletteConfig(
            zz: intval($baseColors[0], 16),
            zn: intval($baseColors[1], 16),
            nn: intval($baseColors[2], 16),
            nb: intval($baseColors[3], 16),
            bb: intval($baseColors[4], 16),
            zb: intval($baseColors[5], 16),
            r11: intval($redData[0], 16),
            r12: intval($redData[1], 16),
            r13: intval($redData[2], 16),
            r21: intval($greenData[0], 16),
            r22: intval($greenData[1], 16),
            r23: intval($greenData[2], 16),
            r31: intval($blueData[0], 16),
            r32: intval($blueData[1], 16),
            r33: intval($blueData[2], 16),
        );
    }

    /**
     * @return array<int, int>
     */
    private function generateColors(PaletteConfig $config): array
    {
        $colors = [];
        for ($colorIndex = 0; $colorIndex < 16; $colorIndex++) {
            $bright = ($colorIndex >> 3) & 1;
            $green = ($colorIndex >> 2) & 1;
            $red = ($colorIndex >> 1) & 1;
            $blue = $colorIndex & 1;

            $zero = $config->zz;
            $one = $bright === 1 ? $config->bb : $config->nn;

            $r = (1 - $red) * $zero + $red * $one;
            $g = (1 - $green) * $zero + $green * $one;
            $b = (1 - $blue) * $zero + $blue * $one;

            $redChannel = (int)round(($r * $config->r11 + $g * $config->r12 + $b * $config->r13) / 0xFF);
            $greenChannel = (int)round(($r * $config->r21 + $g * $config->r22 + $b * $config->r23) / 0xFF);
            $blueChannel = (int)round(($r * $config->r31 + $g * $config->r32 + $b * $config->r33) / 0xFF);

            $colors[$colorIndex] = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }
        return $colors;
    }

    /**
     * @return array<int, int>
     */
    private function generateGigaColors(PaletteConfig $config): array
    {
        $levels = [
            'ZZ' => $config->zz,
            'ZN' => $config->zn,
            'ZB' => $config->zb,
            'NZ' => $config->zn,
            'NN' => $config->nn,
            'NB' => $config->nb,
            'BZ' => $config->zb,
            'BN' => $config->nb,
            'BB' => $config->bb,
        ];

        $gigaColors = [];
        for ($index1 = 0; $index1 < 16; $index1++) {
            $bright1 = ($index1 >> 3) & 1;
            $green1 = ($index1 >> 2) & 1;
            $red1 = ($index1 >> 1) & 1;
            $blue1 = $index1 & 1;

            for ($index2 = 0; $index2 < 16; $index2++) {
                $bright2 = ($index2 >> 3) & 1;
                $green2 = ($index2 >> 2) & 1;
                $red2 = ($index2 >> 1) & 1;
                $blue2 = $index2 & 1;

                $r = $levels[$this->channelLevel($bright1, $red1) . $this->channelLevel($bright2, $red2)];
                $g = $levels[$this->channelLevel($bright1, $green1) . $this->channelLevel($bright2, $green2)];
                $b = $levels[$this->channelLevel($bright1, $blue1) . $this->channelLevel($bright2, $blue2)];

                $redChannel = (int)round(($r * $config->r11 + $g * $config->r12 + $b * $config->r13) / 0xFF);
                $greenChannel = (int)round(($r * $config->r21 + $g * $config->r22 + $b * $config->r23) / 0xFF);
                $blueChannel = (int)round(($r * $config->r31 + $g * $config->r32 + $b * $config->r33) / 0xFF);

                $gigaColors[($index1 << 4) | $index2] = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
            }
        }
        return $gigaColors;
    }

    private function channelLevel(int $bright, int $bit): string
    {
        if ($bit === 0) {
            return 'Z';
        }
        return $bright === 1 ? 'B' : 'N';
    }
}
