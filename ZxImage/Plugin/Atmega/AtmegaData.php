<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Atmega;

final readonly class AtmegaData
{
    /**
     * @param list<int> $pixelsArray
     * @param list<int> $paletteBytes
     */
    public function __construct(
        public array $pixelsArray,
        public array $paletteBytes,
    ) {
    }
}
