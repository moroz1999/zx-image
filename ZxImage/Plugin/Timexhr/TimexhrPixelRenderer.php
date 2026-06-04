<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Timexhr;

use GdImage;
use RuntimeException;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;

final readonly class TimexhrPixelRenderer
{
    public function render(ParsedScreen $parsedScreen, ColorTable $colorTable, int $width, int $height): GdImage
    {
        $inkColor = $parsedScreen->attributes->inkMap[0][0];
        $paperColor = $parsedScreen->attributes->paperMap[0][0];
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Unable to create GD image');
        }

        foreach ($parsedScreen->pixelsData as $rowY => $row) {
            $y = $rowY * 2;
            foreach ($row as $x => $pixel) {
                $color = $colorTable->colors[$pixel === 1 ? $inkColor : $paperColor];
                imagesetpixel($image, $x, $y, $color);
                imagesetpixel($image, $x, $y + 1, $color);
            }
        }

        return $image;
    }
}
