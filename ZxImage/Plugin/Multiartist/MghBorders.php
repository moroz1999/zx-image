<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Multiartist;

final readonly class MghBorders
{
    public function __construct(
        public ?int $border1,
        public ?int $border2,
    ) {
    }
}
