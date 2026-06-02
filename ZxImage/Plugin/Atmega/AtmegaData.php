<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Atmega;

final readonly class AtmegaData
{
    /**
     * @param int[] $pixelsArray
     * @param int[] $paletteBytes
     */
    public function __construct(
        public array $pixelsArray,
        public array $paletteBytes,
    ) {
    }
}
