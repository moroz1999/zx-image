<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sl2;

final readonly class Sl2Data
{
    public function __construct(
        /** @var list<int> */
        public array $pixelsBytes,
    ) {
    }
}
