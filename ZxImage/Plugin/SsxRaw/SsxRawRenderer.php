<?php

declare(strict_types=1);

namespace ZxImage\Plugin\SsxRaw;

use GdImage;
use ZxImage\Dto\PaletteConfig;

final readonly class SsxRawRenderer
{
    private const int BRIGHTNESS_MULTIPLIER = 36;

    /**
     * @param int[] $pixelsBytes
     */
    public function render(array $pixelsBytes, int $width, int $height, PaletteConfig $config): GdImage
    {
        $image = imagecreatetruecolor($width, $height * 2);
        $m = self::BRIGHTNESS_MULTIPLIER;
        $x = 0;
        $y = 0;

        foreach ($pixelsBytes as $clutItem) {
            $bright = ($clutItem >> 3) & 1;
            $r = ((($clutItem >> 5) & 1) * 4 + (($clutItem >> 1) & 1) * 2 + $bright) * $m;
            $g = ((($clutItem >> 6) & 1) * 4 + (($clutItem >> 2) & 1) * 2 + $bright) * $m;
            $b = ((($clutItem >> 4) & 1) * 4 + ($clutItem & 1) * 2 + $bright) * $m;

            $red = (int)round(($r * $config->r11 + $g * $config->r12 + $b * $config->r13) / 0xFF);
            $green = (int)round(($r * $config->r21 + $g * $config->r22 + $b * $config->r23) / 0xFF);
            $blue = (int)round(($r * $config->r31 + $g * $config->r32 + $b * $config->r33) / 0xFF);

            $rgb = $red * 0x010000 + $green * 0x0100 + $blue;
            imagesetpixel($image, $x, $y * 2, $rgb);
            imagesetpixel($image, $x, $y * 2 + 1, $rgb);

            $x++;
            if ($x >= $width) {
                $x = 0;
                $y++;
            }
        }
        return $image;
    }
}
