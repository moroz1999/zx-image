<?php

declare(strict_types=1);

namespace ZxImage\Filter;

use GdImage;
use Override as OverrideAttribute;

final class Atari extends Filter
{
    #[OverrideAttribute]
    public function apply(GdImage $image, ?GdImage $srcImage = null): GdImage
    {
        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);
        $halfWidth = (int)($srcWidth / 2);

        $dstImage2 = $this->createImage($halfWidth, $srcHeight);
        imagecopyresampled($dstImage2, $image, 0, 0, 0, 0, $halfWidth, $srcHeight, $srcWidth, $srcHeight);
        imagecopyresampled($image, $dstImage2, 0, 0, 0, 0, $srcWidth, $srcHeight, $halfWidth, $srcHeight);
        return $image;
    }
}
