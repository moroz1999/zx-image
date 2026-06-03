<?php

declare(strict_types=1);

namespace ZxImage\Filter;

use GdImage;
use Override as OverrideAttribute;

class MinSize384 extends Filter
{
    protected int $canvasWidth = 384;
    protected int $canvasHeight = 288;

    #[OverrideAttribute]
    public function apply(GdImage $image, ?GdImage $srcImage = null): GdImage
    {
        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);

        $dstWidth = $srcWidth;
        $dstHeight = $srcHeight;

        $widthScale = null;
        $heightScale = null;
        if ($srcWidth < $this->canvasWidth) {
            $widthScale = intdiv($this->canvasWidth, $srcWidth);
            if ($srcHeight < $this->canvasHeight) {
                $heightScale = intdiv($this->canvasHeight, $srcHeight);
            }
        }
        if ($widthScale !== null && $heightScale !== null) {
            $scale = min($widthScale, $heightScale);
            $dstWidth = $srcWidth * $scale;
            $dstHeight = $srcHeight * $scale;
        }

        $dstImage = $this->createImage($this->canvasWidth, $this->canvasHeight);
        imagefill($dstImage, 0, 0, 0);
        imagecopyresized(
            $dstImage,
            $image,
            intdiv($this->canvasWidth - $dstWidth, 2),
            intdiv($this->canvasHeight - $dstHeight, 2),
            0,
            0,
            $dstWidth,
            $dstHeight,
            $srcWidth,
            $srcHeight,
        );

        return $dstImage;
    }
}
