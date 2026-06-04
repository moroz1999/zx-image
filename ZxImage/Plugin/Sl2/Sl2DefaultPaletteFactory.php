<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sl2;

use ZxImage\Dto\IndexedPaletteEntry;

final readonly class Sl2DefaultPaletteFactory
{
    /**
     * @return list<IndexedPaletteEntry>
     */
    public function create(int $paletteSize): array
    {
        $paletteEntries = [];

        for ($index = 0; $index < $paletteSize; $index++) {
            $paletteEntries[] = new IndexedPaletteEntry(
                $index,
                $index % 4 === 0 ? 0 : 1,
            );
        }

        return $paletteEntries;
    }
}
