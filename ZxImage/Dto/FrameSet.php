<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class FrameSet
{
    /**
     * @param iterable<Frame> $frames
     */
    public function __construct(
        public iterable $frames,
        public RenderSettings $renderSettings,
        public RenderGeometry $geometry,
        public ColorTable $colorTable,
        public ?int $interlaceLineHeight = null,
        public ?int $frameCount = null,
    ) {
    }

    public function getFrameCount(): int
    {
        return $this->frameCount ?? (is_array($this->frames) ? count($this->frames) : 0);
    }
}
