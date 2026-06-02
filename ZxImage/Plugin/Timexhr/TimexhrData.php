<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Timexhr;

final readonly class TimexhrData
{
    /**
     * @param int[] $pixelsBytes
     */
    public function __construct(
        public array $pixelsBytes,
        public int $attributeByte,
    ) {
    }
}
