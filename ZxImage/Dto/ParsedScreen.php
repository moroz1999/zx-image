<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class ParsedScreen
{
    public function __construct(
        /** @var array<int, array<int, int>> */
        public array $pixelsData,
        public AttributeMap $attributes,
        /** @var array<int, int> */
        public array $colorOverrides = [],
        /** @var array<int, int> */
        public array $borderBytes = [],
        /** @var array<int, array<int, int>> */
        public array $borderPixels = [],
    ) {
    }
}
