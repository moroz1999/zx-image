<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Tricolor;

final readonly class TricolorData
{
    public function __construct(
        /** @var list{list<int>, list<int>, list<int>} */
        public array $screenPixelsBytes,
    ) {
    }
}
