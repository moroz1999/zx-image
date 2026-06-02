<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sca;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderSettings;

final readonly class ScaData
{
    /**
     * @param int[] $delays
     * @param RawScreen[] $screens
     */
    public function __construct(
        public PluginGeometry $geometry,
        public RenderSettings $renderSettings,
        public array $delays,
        public array $screens,
    ) {
    }
}
