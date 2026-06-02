<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Grf;

use ZxImage\Dto\PluginGeometry;

final readonly class GrfData
{
    /**
     * @param int[] $paletteBytes
     * @param int[] $pixelsArray
     * @param int[] $attributesArray
     */
    public function __construct(
        public PluginGeometry $geometry,
        public array $paletteBytes,
        public array $pixelsArray,
        public array $attributesArray,
    ) {
    }
}
