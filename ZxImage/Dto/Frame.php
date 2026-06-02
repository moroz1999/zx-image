<?php

declare(strict_types=1);

namespace ZxImage\Dto;

use GdImage;

final readonly class Frame
{
    public function __construct(
        public GdImage $image,
        public int $delayCentiseconds = 0,
        public ?RenderSettings $renderSettings = null,
    ) {
    }
}
