<?php

declare(strict_types=1);

namespace ZxImage\Service\Image;

use GdImage;
use ZxImage\Dto\ColorTable;

final readonly class BorderApplier
{
    public function apply(
        GdImage $image,
        ?int $border,
        ColorTable $colorTable,
        int $width,
        int $height,
        int $borderWidth,
        int $borderHeight,
        bool $usesBorder = true,
    ): GdImage {
        if ($usesBorder === false || $border === null) {
            return $image;
        }

        $resultImage = imagecreatetruecolor(
            $width + $borderWidth * 2,
            $height + $borderHeight * 2,
        );
        $color = $colorTable->colors[$border];
        imagefill($resultImage, 0, 0, $color);
        imagecopy($resultImage, $image, $borderWidth, $borderHeight, 0, 0, $width, $height);

        return $resultImage;
    }
}
