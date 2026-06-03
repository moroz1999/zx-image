<?php

declare(strict_types=1);

namespace ZxImage\Service\Image;

use GdImage;

final readonly class ImageResizer
{
    public function __construct(
        private FilterApplier $filterApplier = new FilterApplier(),
    ) {
    }

    /**
     * @param string[] $preFilters
     * @param string[] $postFilters
     */
    public function resize(GdImage $image, float $zoom, array $preFilters, array $postFilters): GdImage
    {
        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);
        imagegammacorrect($image, 2.2, 1.0);

        $dstWidth = $srcWidth;
        $dstHeight = $srcHeight;
        if (in_array($zoom, [0.25, 0.5, 2.0, 3.0, 4.0], true)) {
            $dstWidth = (int)($srcWidth * $zoom);
            $dstHeight = (int)($srcHeight * $zoom);
        }

        $image = $this->filterApplier->applyPreFilters($image, $preFilters);

        if ($zoom === 1.0) {
            $dstImage = $image;
        } else {
            $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            imagecopyresampled($dstImage, $image, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        }

        $dstImage = $this->filterApplier->applyPostFilters($image, $dstImage, $postFilters);
        imagegammacorrect($dstImage, 1.0, 2.2);

        return $dstImage;
    }
}
