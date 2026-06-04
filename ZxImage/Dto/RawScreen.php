<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class RawScreen
{
    public function __construct(
        /** @var array<int, int> */
        public array $pixelsBytes,
        /** @var array<int, int> */
        public array $attributesBytes,
        /** @var array<int, int> */
        public array $borderBytes = [],
    ) {
    }
}
