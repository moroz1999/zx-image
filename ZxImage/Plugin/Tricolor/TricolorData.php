<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Tricolor;

readonly class TricolorData
{
    public function __construct(
        /** @var array<int, int[]> */
        public array $screenPixelsBytes,
    ) {
    }
}
