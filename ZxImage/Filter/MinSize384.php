<?php

declare(strict_types=1);

namespace ZxImage\Filter;

class MinSize384 extends Filter
{
    protected $canvasWidth = 384;
    protected $canvasHeight = 288;

    public function apply($image, $srcImage = false)
    {
        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);

        $dstWidth = $srcWidth;
        $dstHeight = $srcHeight;

        $widthScale = null;
        $heightScale = null;
        if ($srcWidth < $this->canvasWidth) {
            $widthScale = floor($this->canvasWidth / $srcWidth);
            if ($srcHeight < $this->canvasHeight) {
                $heightScale = floor($this->canvasHeight / $srcHeight);
            }
        }
        if ($widthScale && $heightScale) {
            $scale = min($widthScale, $heightScale);
            $dstWidth = $srcWidth * $scale;
            $dstHeight = $srcHeight * $scale;
        }

        $dstImage = imagecreatetruecolor($this->canvasWidth, $this->canvasHeight);
        imagefill($dstImage, 0, 0, 0);
        imagecopyresized($dstImage, $image, (int)(($this->canvasWidth - $dstWidth) / 2), (int)(($this->canvasHeight - $dstHeight) / 2), 0, 0, (int)$dstWidth, (int)$dstHeight, (int)$srcWidth, (int)$srcHeight);

        return $dstImage;
    }
}