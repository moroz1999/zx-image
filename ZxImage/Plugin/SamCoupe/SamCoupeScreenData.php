<?php

declare(strict_types=1);

namespace ZxImage\Plugin\SamCoupe;

final readonly class SamCoupeScreenData
{
    /**
     * @param list<int> $pixelsBytes
     * @param list<int> $paletteBytes
     */
    public function __construct(
        public array $pixelsBytes,
        public array $paletteBytes,
    ) {
    }
}
