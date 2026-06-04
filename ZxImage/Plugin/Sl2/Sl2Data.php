<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sl2;

use ZxImage\Dto\IndexedPaletteEntry;

final readonly class Sl2Data
{
    /**
     * @param list<int>                 $pixelsBytes
     * @param list<IndexedPaletteEntry> $paletteEntries
     */
    public function __construct(
        public array $pixelsBytes,
        public array $paletteEntries,
        public int $width,
        public int $height,
    ) {
    }
}
