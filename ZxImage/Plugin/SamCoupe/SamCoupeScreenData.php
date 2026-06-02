<?php

declare(strict_types=1);

namespace ZxImage\Plugin\SamCoupe;

final readonly class SamCoupeScreenData
{
    /**
     * @param int[] $pixelsBytes
     * @param int[] $paletteBytes
     */
    public function __construct(
        public array $pixelsBytes,
        public array $paletteBytes,
    ) {
    }
}
