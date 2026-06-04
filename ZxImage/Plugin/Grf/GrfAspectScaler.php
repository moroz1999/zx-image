<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Grf;

use GdImage;
use RuntimeException;

final readonly class GrfAspectScaler
{
    private const float ASPECT_RATIO = 1.6384;

    public function scale(GdImage $srcImage): GdImage
    {
        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);
        imagegammacorrect($srcImage, 2.2, 1.0);

        $dstWidth = $srcWidth;
        $dstHeight = (int)((float)$srcHeight * self::ASPECT_RATIO);

        $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
        if ($dstImage === false) {
            throw new RuntimeException('Unable to create GD image');
        }
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        imagecopyresized($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        imagegammacorrect($dstImage, 1.0, 2.2);

        return $dstImage;
    }
}
