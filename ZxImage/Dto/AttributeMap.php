<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class AttributeMap
{
    public function __construct(
        /** @var array<int, array<int, int>> */
        public array $inkMap,
        /** @var array<int, array<int, int>> */
        public array $paperMap,
        /** @var array<int, array<int, bool>> */
        public array $flashMap,
    ) {
    }
}
