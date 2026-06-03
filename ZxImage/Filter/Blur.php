<?php

declare(strict_types=1);

namespace ZxImage\Filter;

use GdImage;
use Override as OverrideAttribute;

final class Blur extends Filter
{
    private const float RED_LUMINANCE = 0.2126;
    private const float GREEN_LUMINANCE = 0.7152;
    private const float BLUE_LUMINANCE = 0.0722;
    private const float HALO_LUMINANCE_DIVISOR = 2.0;
    private const float ALPHA_MAX = 127.0;
    private const int ALPHA_MULTIPLIER = 0x1000000;
    private const int RED_MULTIPLIER = 0x010000;
    private const int GREEN_MULTIPLIER = 0x0100;
    private const float DIMMED_LINE_INTENSITY = 0.92;
    private const float DIMMED_COLUMN_INTENSITY = 0.8;

    #[OverrideAttribute]
    public function apply(GdImage $image, ?GdImage $srcImage = null): GdImage
    {
        $srcImage ??= $image;

        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);

        $haloImage = $this->createImage($srcWidth, $srcHeight);
        imagealphablending($haloImage, false);
        imagesavealpha($haloImage, true);
        imagecopyresampled($haloImage, $srcImage, 0, 0, 0, 0, $srcWidth, $srcHeight, $srcWidth, $srcHeight);
        imagefilter($haloImage, IMG_FILTER_GAUSSIAN_BLUR);

        for ($j = 0; $j < $srcHeight; $j++) {
            for ($i = 0; $i < $srcWidth; $i++) {
                $rgb = $this->getPixelColor($haloImage, $i, $j);

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $luminance = (
                    (self::RED_LUMINANCE * (float)$r)
                    + (self::GREEN_LUMINANCE * (float)$g)
                    + (self::BLUE_LUMINANCE * (float)$b)
                ) / self::HALO_LUMINANCE_DIVISOR;
                $color = (int)(self::ALPHA_MAX - $luminance) * self::ALPHA_MULTIPLIER + $rgb;
                imagesetpixel($haloImage, $i, $j, $color);
            }
        }

        $dstWidth = imagesx($image);
        $dstHeight = imagesy($image);

        $blurImage = $this->createImage($dstWidth, $dstHeight);
        imagealphablending($blurImage, false);
        imagesavealpha($blurImage, true);
        imagecopyresampled($image, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

        $gaussian = [[1.0, 2.0, 1.0], [2.0, 4.0, 2.0], [1.0, 2.0, 1.0]];
        imageconvolution($image, $gaussian, 16, 0);
        imagecopyresampled($blurImage, $haloImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

        imagecopymerge($image, $blurImage, 0, 0, 0, 0, $dstWidth, $dstHeight, 50);

        imagegammacorrect($image, 1, 1.5);

        $vert1 = 1.0;
        $vert2 = 1.0;

        $ycounter = 0;
        for ($y = 0; $y < $dstHeight; $y++) {
            if ($y % 2 === 1) {
                $int = self::DIMMED_LINE_INTENSITY;
            } else {
                $int = 1.0;
            }

            if ($ycounter > 1) {
                $ycounter = 0;
                if ($vert1 === 1.0) {
                    $vert1 = self::DIMMED_COLUMN_INTENSITY;
                    $vert2 = 1.0;
                } else {
                    $vert1 = 1.0;
                    $vert2 = self::DIMMED_COLUMN_INTENSITY;
                }
            }
            $ycounter++;

            for ($x = 0; $x < $dstWidth; $x++) {
                $rgb = $this->getPixelColor($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($x % 2 === 1) {
                    $r = (int)ceil((float)$r * $vert1 * $int);
                    $g = (int)ceil((float)$g * $vert1 * $int);
                    $b = (int)ceil((float)$b * $vert1 * $int);
                } else {
                    $r = (int)ceil((float)$r * $vert2 * $int);
                    $g = (int)ceil((float)$g * $vert2 * $int);
                    $b = (int)ceil((float)$b * $vert2 * $int);
                }
                $color = $r * self::RED_MULTIPLIER + $g * self::GREEN_MULTIPLIER + $b;

                imagesetpixel($image, $x, $y, $color);
            }
        }
        return $image;
    }
}
