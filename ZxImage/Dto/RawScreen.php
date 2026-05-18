<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class RawScreen
{
    public function __construct(
        /** @var int[] */
        public readonly array $pixelsBytes,
        /** @var int[] */
        public readonly array $attributesBytes,
        public readonly array $borderBytes = [],
    ) {
    }
}
