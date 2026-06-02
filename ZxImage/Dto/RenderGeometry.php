<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class RenderGeometry
{
    public function __construct(
        public int $width,
        public int $height,
        public int $borderWidth,
        public int $borderHeight,
        public bool $usesBorder,
    ) {
    }
}
