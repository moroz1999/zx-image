<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class ColorTable
{
    public function __construct(
        public readonly PaletteConfig $config,
        /** @var int[] */
        public readonly array $colors,
        /** @var int[] */
        public readonly array $gigaColors,
    ) {
    }
}
