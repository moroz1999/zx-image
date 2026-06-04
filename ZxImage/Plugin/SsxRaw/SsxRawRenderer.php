<?php

declare(strict_types=1);

namespace ZxImage\Plugin\SsxRaw;

use GdImage;
use RuntimeException;
use ZxImage\Dto\PaletteConfig;

final readonly class SsxRawRenderer
{
    private const int BRIGHTNESS_MULTIPLIER = 36;

    /**
     * @param array<int, int> $pixelsBytes
     */
    public function render(array $pixelsBytes, int $width, int $height, PaletteConfig $config): GdImage
    {
        $image = imagecreatetruecolor($width, $height * 2);
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }
        $brightnessMultiplier = self::BRIGHTNESS_MULTIPLIER;
        $x = 0;
        $y = 0;

        foreach ($pixelsBytes as $clutItem) {
            $bright = ($clutItem >> 3) & 1;
            $redValue = ((($clutItem >> 5) & 1) * 4 + (($clutItem >> 1) & 1) * 2 + $bright) * $brightnessMultiplier;
            $greenValue = ((($clutItem >> 6) & 1) * 4 + (($clutItem >> 2) & 1) * 2 + $bright) * $brightnessMultiplier;
            $blueValue = ((($clutItem >> 4) & 1) * 4 + ($clutItem & 1) * 2 + $bright) * $brightnessMultiplier;

            $red = (int)round(
                ($redValue * $config->r11 + $greenValue * $config->r12 + $blueValue * $config->r13) / 0xFF
            );
            $green = (int)round(
                ($redValue * $config->r21 + $greenValue * $config->r22 + $blueValue * $config->r23) / 0xFF
            );
            $blue = (int)round(
                ($redValue * $config->r31 + $greenValue * $config->r32 + $blueValue * $config->r33) / 0xFF
            );

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
