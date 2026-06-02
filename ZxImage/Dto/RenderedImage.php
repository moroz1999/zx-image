<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class RenderedImage
{
    public function __construct(
        public string $binary,
        public string $mime,
    ) {
    }
}
