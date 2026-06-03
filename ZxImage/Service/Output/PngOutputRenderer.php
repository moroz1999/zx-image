<?php

declare(strict_types=1);

namespace ZxImage\Service\Output;

use ZxImage\Dto\FrameSet;
use ZxImage\Dto\RenderedImage;
use ZxImage\Service\ImageEncoder;

final readonly class PngOutputRenderer
{
    public function __construct(
        private FrameFinalizer $frameFinalizer = new FrameFinalizer(),
        private ImageEncoder $imageEncoder = new ImageEncoder(),
    ) {
    }

    public function render(FrameSet $frameSet): RenderedImage
    {
        $frame = $frameSet->frames[0];
        $image = $this->frameFinalizer->finalize($frame, $frameSet);

        return new RenderedImage($this->imageEncoder->toPng($image), 'image/png');
    }
}
