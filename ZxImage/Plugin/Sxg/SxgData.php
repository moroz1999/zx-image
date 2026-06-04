<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sxg;

use ZxImage\Dto\PluginGeometry;

final readonly class SxgData
{
    /**
     * @param list<int> $paletteWords
     * @param list<int> $pixelsBytes
     */
    public function __construct(
        public PluginGeometry $geometry,
        public int $format,
        public array $paletteWords,
        public array $pixelsBytes,
    ) {
    }
}
