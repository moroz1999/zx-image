<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Dto\FrameSet;
use ZxImage\Dto\RenderedImage;
use ZxImage\Service\Output\AnimatedGifOutputRenderer;
use ZxImage\Service\Output\PngOutputRenderer;

final readonly class OutputRenderer
{
    public function __construct(
        private PngOutputRenderer $pngOutputRenderer = new PngOutputRenderer(),
        private AnimatedGifOutputRenderer $animatedGifOutputRenderer = new AnimatedGifOutputRenderer(),
    ) {
    }

    public function render(FrameSet $frameSet): RenderedImage
    {
        if ($frameSet->getFrameCount() === 1) {
            return $this->pngOutputRenderer->render($frameSet);
        }

        return $this->animatedGifOutputRenderer->render($frameSet);
    }
}
