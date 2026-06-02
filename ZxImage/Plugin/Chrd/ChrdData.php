<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Chrd;

use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;

final readonly class ChrdData
{
    public function __construct(
        public int $colorType,
        public PluginGeometry $geometry,
        public ParsedScreen $screen1,
        public ParsedScreen $screen2,
    ) {
    }
}
