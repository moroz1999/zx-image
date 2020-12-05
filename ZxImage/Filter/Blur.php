<?php

declare(strict_types=1);

namespace ZxImage\Filter;

class Blur extends Filter
{
    /**
     * @param $image
     * @param bool|resource $srcImage
     * @return mixed
     */
    public function apply($image, $srcImage = false)
    {
        if (!$srcImage) {
            $srcImage = $image;
        }

        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);

        $haloImage = imagecreatetruecolor($srcWidth, $srcHeight);
        imagealphablending($haloImage, false);
        imagesavealpha($haloImage, true);
        imagecopyresampled($haloImage, $srcImage, 0, 0, 0, 0, $srcWidth, $srcHeight, $srcWidth, $srcHeight);
        imagefilter($haloImage, IMG_FILTER_GAUSSIAN_BLUR);

        for ($j = 0; $j < $srcHeight; $j++) {
            for ($i = 0; $i < $srcWidth; $i++) {
                $rgb = imagecolorat($haloImage, $i, $j);

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $luminance = ((0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b)) / 2;
                $color = ((int)(127 - $luminance)) * 0x1000000 + $rgb;
                imagesetpixel($haloImage, $i, $j, $color);
            }
        }

        $dstWidth = imagesx($image);
        $dstHeight = imagesy($image);

        $blurImage = imagecreatetruecolor($dstWidth, $dstHeight);
        imagealphablending($blurImage, false);
        imagesavealpha($blurImage, true);
        imagecopyresampled($image, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

        $gaussian = [[1, 2, 1], [2, 4, 2], [1, 2, 1]];
        imageconvolution($image, $gaussian, 16, 0);
        imagecopyresampled($blurImage, $haloImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

        imagecopymerge($image, $blurImage, 0, 0, 0, 0, $dstWidth, $dstHeight, 50);

        imagegammacorrect($image, 1, 1.5);

        $vert1 = 1;
        $vert2 = 1;

        $ycounter = 0;
        for ($y = 0; $y < $dstHeight; $y++) {
            if ($y % 2) {
                $int = 0.92;
            } else {
                $int = 1;
            }

            if ($ycounter > 1) {
                $ycounter = 0;
                if ($vert1 == 1) {
                    $vert1 = 0.8;
                    $vert2 = 1;
                } else {
                    $vert1 = 1;
                    $vert2 = 0.8;
                }
            }
            $ycounter++;

            for ($x = 0; $x < $dstWidth; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($x % 2) {
                    $r = (int)ceil($r * $vert1 * $int);
                    $g = (int)ceil($g * $vert1 * $int);
                    $b = (int)ceil($b * $vert1 * $int);
                } else {
                    $r = (int)ceil($r * $vert2 * $int);
                    $g = (int)ceil($g * $vert2 * $int);
                    $b = (int)ceil($b * $vert2 * $int);
                }
                $color = $r * 0x010000 + $g * 0x0100 + $b;

                imagesetpixel($image, $x, $y, $color);
            }
        }
        return $image;
    }
}