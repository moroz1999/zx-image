<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class IndexedPaletteEntry
{
    public function __construct(
        public int $byte1,
        public int $byte2,
    ) {}
}
