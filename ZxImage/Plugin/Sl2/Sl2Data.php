<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sl2;

readonly class Sl2Data
{
    public function __construct(
        /** @var int[] */
        public array $pixelsBytes,
    ) {
    }
}
