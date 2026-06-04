<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sl2;

use ZxImage\Dto\IndexedPaletteEntry;

final readonly class Sl2DefaultPaletteFactory
{
    private const int PALETTE_SIZE = 256;

    /**
     * @return list<IndexedPaletteEntry>
     */
    public function create(): array
    {
        $paletteEntries = [];

        for ($index = 0; $index < self::PALETTE_SIZE; $index++) {
            $paletteEntries[] = new IndexedPaletteEntry(
                $index,
                $index % 4 === 0 ? 0 : 1,
            );
        }

        return $paletteEntries;
    }
}
