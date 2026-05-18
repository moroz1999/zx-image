<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class AttributeMap
{
    public function __construct(
        /** @var int[][] */
        public array $inkMap,
        /** @var int[][] */
        public array $paperMap,
        /** @var bool[][] */
        public array $flashMap,
    ) {
    }
}
