<?php

declare(strict_types=1);

namespace ZxImage\Plugin\SsxRaw;

final readonly class SsxRawData
{
    public function __construct(
        /** @var list<int> */
        public array $pixelsBytes,
    ) {
    }
}
