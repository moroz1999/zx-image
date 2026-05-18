<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Filter\Filter;

readonly class ImageProcessor
{
    public function applyBorder(
        GdImage $image,
        ?int $border,
        ColorTable $colorTable,
        int $width,
        int $height,
        int $borderWidth,
        int $borderHeight,
        bool $usesBorder = true,
    ): GdImage {
        if ($usesBorder && $border !== null) {
            $resultImage = imagecreatetruecolor(
                $width + $borderWidth * 2,
                $height + $borderHeight * 2,
            );
            $color = $colorTable->colors[$border];
            imagefill($resultImage, 0, 0, $color);
            imagecopy($resultImage, $image, $borderWidth, $borderHeight, 0, 0, $width, $height);
            return $resultImage;
        }
        return $image;
    }

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

        $image = $this->applyPreFilters($image, $preFilters);

        if ($zoom === 1.0) {
            $dstImage = $image;
        } else {
            $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            imagecopyresampled($dstImage, $image, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        }

        $dstImage = $this->applyPostFilters($image, $dstImage, $postFilters);
        imagegammacorrect($dstImage, 1.0, 2.2);
        return $dstImage;
    }

    public function rotate(GdImage $image, int $rotation): GdImage
    {
        if ($rotation === 0) {
            return $image;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $result = match ($rotation) {
            90, 270 => imagecreatetruecolor($height, $width),
            180 => imagecreatetruecolor($width, $height),
            default => null,
        };

        if ($result === null) {
            return $image;
        }

        for ($i = 0; $i < $width; $i++) {
            for ($j = 0; $j < $height; $j++) {
                $pixel = imagecolorat($image, $i, $j);
                match ($rotation) {
                    90 => imagesetpixel($result, ($height - 1) - $j, $i, $pixel),
                    180 => imagesetpixel($result, $width - $i, ($height - 1) - $j, $pixel),
                    270 => imagesetpixel($result, $j, $width - $i, $pixel),
                    default => null,
                };
            }
        }

        return $result;
    }

    public function interlaceMix(GdImage $image1, GdImage $image2, int $lineHeight, float $zoom): void
    {
        $multiplier = ($zoom === 3.0 || $zoom === 4.0) ? 2 : 1;

        $width = imagesx($image1);
        $height = imagesy($image1);

        for ($y = 0; $y < $height; $y++) {
            if ((int)($y / ($lineHeight * $multiplier)) % 2 === 1) {
                for ($x = 0; $x < $width; $x++) {
                    $pixel1 = imagecolorat($image1, $x, $y);
                    $pixel2 = imagecolorat($image2, $x, $y);
                    imagesetpixel($image2, $x, $y, $pixel1);
                    imagesetpixel($image1, $x, $y, $pixel2);
                }
            }
        }
    }

    private function applyPreFilters(GdImage $image, array $filters): GdImage
    {
        foreach ($filters as $filterType) {
            $className = '\\ZxImage\\Filter\\' . ucfirst($filterType);
            if (class_exists($className)) {
                /** @var Filter $filter */
                $filter = new $className();
                $image = $filter->apply($image);
            }
        }
        return $image;
    }

    private function applyPostFilters(GdImage $srcImage, GdImage $dstImage, array $filters): GdImage
    {
        foreach ($filters as $filterType) {
            $className = '\\ZxImage\\Filter\\' . ucfirst($filterType);
            if (class_exists($className)) {
                /** @var Filter $filter */
                $filter = new $className();
                $dstImage = $filter->apply($dstImage, $srcImage);
            }
        }
        return $dstImage;
    }
}
