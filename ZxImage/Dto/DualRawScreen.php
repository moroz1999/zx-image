<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class DualRawScreen
{
    public function __construct(
        public readonly RawScreen $first,
        public readonly RawScreen $second,
    ) {
    }
}
