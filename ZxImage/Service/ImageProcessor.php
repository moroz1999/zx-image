<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Service\Image\BorderApplier;
use ZxImage\Service\Image\ImageResizer;
use ZxImage\Service\Image\ImageRotator;
use ZxImage\Service\Image\InterlaceMixer;

final readonly class ImageProcessor
{
    public function __construct(
        private BorderApplier $borderApplier = new BorderApplier(),
        private ImageResizer $imageResizer = new ImageResizer(),
        private ImageRotator $imageRotator = new ImageRotator(),
        private InterlaceMixer $interlaceMixer = new InterlaceMixer(),
    ) {
    }

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
        return $this->borderApplier->apply(
            $image,
            $border,
            $colorTable,
            $width,
            $height,
            $borderWidth,
            $borderHeight,
            $usesBorder,
        );
    }

    /**
     * @param list<string> $preFilters
     * @param list<string> $postFilters
     */
    public function resize(GdImage $image, float $zoom, array $preFilters, array $postFilters): GdImage
    {
        return $this->imageResizer->resize($image, $zoom, $preFilters, $postFilters);
    }

    public function rotate(GdImage $image, int $rotation): GdImage
    {
        return $this->imageRotator->rotate($image, $rotation);
    }

    public function interlaceMix(GdImage $image1, GdImage $image2, int $lineHeight, float $zoom): void
    {
        $this->interlaceMixer->mix($image1, $image2, $lineHeight, $zoom);
    }
}
