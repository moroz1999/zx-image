<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class FrameSet
{
    /**
     * @param Frame[] $frames
     */
    public function __construct(
        public array $frames,
        public RenderSettings $renderSettings,
        public RenderGeometry $geometry,
        public ColorTable $colorTable,
        public ?int $interlaceLineHeight = null,
    ) {
    }
}
