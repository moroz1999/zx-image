<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class ParsedScreen
{
    public function __construct(
        /** @var int[][] */
        public array $pixelsData,
        public AttributeMap $attributes,
        /** @var int[] */
        public array $colorOverrides = [],
        public array $borderData = [],
    ) {
    }
}
