<?php

declare(strict_types=1);

namespace ZxImage\Plugin\SsxRaw;

readonly class SsxRawData
{
    public function __construct(
        /** @var int[] */
        public array $pixelsBytes,
    ) {
    }
}
