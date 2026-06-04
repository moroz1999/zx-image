<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class ColorTable
{
    public function __construct(
        public readonly PaletteConfig $config,
        /** @var array<int, int> */
        public readonly array $colors,
        /** @var array<int, int> */
        public readonly array $gigaColors,
    ) {
    }
}
