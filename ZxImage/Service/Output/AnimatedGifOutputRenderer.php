<?php

declare(strict_types=1);

namespace ZxImage\Service\Output;

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

        if ($frameSet->interlaceLineHeight === null) {
            foreach ($frameSet->frames as $frame) {
                $image = $this->frameFinalizer->finalize($frame, $frameSet);
                $gifFrames[] = $this->imageEncoder->toPaletteGif($image);
                $delays[] = $frame->delayCentiseconds;
            }
        } else {
            $this->renderInterlacePairs($frameSet, $frameSet->interlaceLineHeight, $gifFrames, $delays);
        }

        return new RenderedImage($this->imageEncoder->toAnimatedGif($gifFrames, $delays), 'image/gif');
    }

    /**
     * @param string[] $gifFrames
     * @param int[] $delays
     */
    private function renderInterlacePairs(
        FrameSet $frameSet,
        int $interlaceLineHeight,
        array &$gifFrames,
        array &$delays,
    ): void
    {
        for ($i = 0; $i + 1 < count($frameSet->frames); $i += 2) {
            $firstFrame = $frameSet->frames[$i];
            $secondFrame = $frameSet->frames[$i + 1];
            $firstImage = $this->frameFinalizer->finalize($firstFrame, $frameSet);
            $secondImage = $this->frameFinalizer->finalize($secondFrame, $frameSet);
            $this->imageProcessor->interlaceMix(
                $firstImage,
                $secondImage,
                $interlaceLineHeight,
                $frameSet->renderSettings->zoom,
            );
            $gifFrames[] = $this->imageEncoder->toPaletteGif($firstImage);
            $gifFrames[] = $this->imageEncoder->toPaletteGif($secondImage);
            $delays[] = $firstFrame->delayCentiseconds;
            $delays[] = $secondFrame->delayCentiseconds;
        }

        if (count($frameSet->frames) % 2 === 1) {
            $lastFrame = $frameSet->frames[count($frameSet->frames) - 1];
            $lastImage = $this->frameFinalizer->finalize($lastFrame, $frameSet);
            $gifFrames[] = $this->imageEncoder->toPaletteGif($lastImage);
            $delays[] = $lastFrame->delayCentiseconds;
        }
    }
}
