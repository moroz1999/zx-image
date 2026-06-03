<?php

declare(strict_types=1);

namespace ZxImage\Service\Image;

use GdImage;
use ZxImage\Enum\FilterType;

final readonly class FilterApplier
{
    /**
     * @param string[] $filters
     */
    public function applyPreFilters(GdImage $image, array $filters): GdImage
    {
        foreach ($filters as $filterType) {
            $filter = FilterType::tryFrom($filterType);
            if ($filter !== null) {
                $image = $filter->createFilter()->apply($image);
            }
        }

        return $image;
    }

    /**
     * @param string[] $filters
     */
    public function applyPostFilters(GdImage $srcImage, GdImage $dstImage, array $filters): GdImage
    {
        foreach ($filters as $filterType) {
            $filter = FilterType::tryFrom($filterType);
            if ($filter !== null) {
                $dstImage = $filter->createFilter()->apply($dstImage, $srcImage);
            }
        }

        return $dstImage;
    }
}
