<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Nxi;

use ZxImage\Dto\IndexedPaletteEntry;

final readonly class NxiData
{
    /**
     * @param list<IndexedPaletteEntry> $paletteEntries
     * @param list<int>                 $pixelsBytes
     */
    public function __construct(
        public array $paletteEntries,
        public array $pixelsBytes,
    ) {
    }
}
