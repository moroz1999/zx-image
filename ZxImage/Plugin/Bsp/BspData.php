<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Bsp;

use ZxImage\Dto\ParsedScreen;

final readonly class BspData
{
    public function __construct(
        public bool $hasGigaData,
        public bool $hasBorderData,
        public int $border1,
        public int $border2,
        public ParsedScreen $screen1,
        public ParsedScreen $screen2,
    ) {
    }
}
