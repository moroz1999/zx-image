<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\RenderedImage;

final readonly class OutputRenderer
{
    public function __construct(
        private ImageProcessor $imageProcessor = new ImageProcessor(),
        private ImageEncoder $imageEncoder = new ImageEncoder(),
    ) {
    }

    public function render(FrameSet $frameSet): RenderedImage
    {
        if (count($frameSet->frames) === 1) {
            $frame = $frameSet->frames[0];
            $image = $this->finalizeFrame($frame, $frameSet);

            return new RenderedImage($this->imageEncoder->toPng($image), 'image/png');
        }

        $gifFrames = [];
        $delays = [];
        $images = [];

        foreach ($frameSet->frames as $frame) {
            $images[] = $this->finalizeFrame($frame, $frameSet);
            $delays[] = $frame->delayCentiseconds;
        }

        $this->applyInterlacePairs($images, $frameSet);

        foreach ($images as $image) {
            $gifFrames[] = $this->imageEncoder->toPaletteGif($image);
        }

        return new RenderedImage($this->imageEncoder->toAnimatedGif($gifFrames, $delays), 'image/gif');
    }

    private function finalizeFrame(Frame $frame, FrameSet $frameSet): GdImage
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
