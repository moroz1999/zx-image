<?php

declare(strict_types=1);

namespace ZxImage\Service\Output;

use GdImage;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Service\ImageProcessor;

final readonly class FrameFinalizer
{
    public function __construct(
        private ImageProcessor $imageProcessor = new ImageProcessor(),
    ) {
    }

    public function finalize(Frame $frame, FrameSet $frameSet): GdImage
    {
        $renderSettings = $frame->renderSettings ?? $frameSet->renderSettings;

        $image = $this->imageProcessor->applyBorder(
            $frame->image,
            $renderSettings->border,
            $frameSet->colorTable,
            $frameSet->geometry->width,
            $frameSet->geometry->height,
            $frameSet->geometry->borderWidth,
            $frameSet->geometry->borderHeight,
            $frameSet->geometry->usesBorder,
        );

        $image = $this->imageProcessor->resize(
            $image,
            $renderSettings->zoom,
            $renderSettings->preFilters,
            $renderSettings->postFilters,
        );

        return $this->imageProcessor->rotate($image, $renderSettings->rotation);
    }
}
