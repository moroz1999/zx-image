<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class RawScreen
{
    public function __construct(
        /** @var int[] */
        public array $pixelsBytes,
        /** @var int[] */
        public array $attributesBytes,
        public array $borderBytes = [],
    ) {
    }
}
