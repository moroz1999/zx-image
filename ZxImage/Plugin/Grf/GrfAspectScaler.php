<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Grf;

use GdImage;

final readonly class GrfAspectScaler
{
    private const float ASPECT_RATIO = 1.6384;

    public function scale(GdImage $srcImage): GdImage
    {
        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);
        imagegammacorrect($srcImage, 2.2, 1.0);

        $dstWidth = $srcWidth;
        $dstHeight = (int)($srcHeight * self::ASPECT_RATIO);

        $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        imagecopyresized($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        imagegammacorrect($dstImage, 1.0, 2.2);

        return $dstImage;
    }
}
