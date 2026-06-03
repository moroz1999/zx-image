<?php

declare(strict_types=1);

namespace ZxImage\Service\Output;

use GdImage;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\RenderedImage;
use ZxImage\Service\ImageEncoder;
use ZxImage\Service\ImageProcessor;

final readonly class AnimatedGifOutputRenderer
{
    public function __construct(
        private FrameFinalizer $frameFinalizer = new FrameFinalizer(),
        private ImageProcessor $imageProcessor = new ImageProcessor(),
        private ImageEncoder $imageEncoder = new ImageEncoder(),
    ) {
    }

    public function render(FrameSet $frameSet): RenderedImage
    {
        $gifFrames = [];
        $delays = [];
        $images = [];

        foreach ($frameSet->frames as $frame) {
            $images[] = $this->frameFinalizer->finalize($frame, $frameSet);
            $delays[] = $frame->delayCentiseconds;
        }

        $this->applyInterlacePairs($images, $frameSet);

        foreach ($images as $image) {
            $gifFrames[] = $this->imageEncoder->toPaletteGif($image);
        }

        return new RenderedImage($this->imageEncoder->toAnimatedGif($gifFrames, $delays), 'image/gif');
    }

    /**
     * @param GdImage[] $images
     */
    private function applyInterlacePairs(array $images, FrameSet $frameSet): void
    {
        if ($frameSet->interlaceLineHeight === null) {
            return;
        }

        for ($i = 0; $i + 1 < count($images); $i += 2) {
            $this->imageProcessor->interlaceMix(
                $images[$i],
                $images[$i + 1],
                $frameSet->interlaceLineHeight,
                $frameSet->renderSettings->zoom,
            );
        }
    }
}
