<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class MghDimensions
{
    public function __construct(
        public int $attributeHeight,
        public int $attributesLength,
        public int $outerAttributesLength,
    ) {}
}
