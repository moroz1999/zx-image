<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class IndexedScreen
{
    public function __construct(
        /** @var int[][] */
        public readonly array $pixelsData,
        /** @var int[] */
        public readonly array $colorTable,
    ) {
    }
}
