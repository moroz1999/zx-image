<?php

declare(strict_types=1);

namespace ZxImage\Filter;

class Atari extends Filter
{
    public function apply($image, $srcImage = false)
    {
        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);

        $dstImage2 = imagecreatetruecolor($srcWidth / 2, $srcHeight);
        imagecopyresampled($dstImage2, $image, 0, 0, 0, 0, $srcWidth / 2, $srcHeight, $srcWidth, $srcHeight);
        imagecopyresampled($image, $dstImage2, 0, 0, 0, 0, $srcWidth, $srcHeight, $srcWidth / 2, $srcHeight);
        return $image;
    }
}