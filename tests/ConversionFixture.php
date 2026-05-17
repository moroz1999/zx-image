<?php

declare(strict_types=1);

namespace ZxImage\Tests;

readonly class ConversionFixture
{
    /**
     * @param list<string> $preFilters
     * @param list<string> $postFilters
     */
    public function __construct(
        public string $type,
        public string $sourceFileName,
        public string $expectedFileName,
        public ?int $border = null,
        public ?string $palette = null,
        public ?string $gigascreenMode = null,
        public float $zoom = 1.0,
        public int $rotation = 0,
        public array $preFilters = [],
        public array $postFilters = [],
    ) {
    }
}
