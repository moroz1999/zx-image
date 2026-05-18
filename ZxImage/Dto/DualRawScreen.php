<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class DualRawScreen
{
    public function __construct(
        public readonly RawScreen $first,
        public readonly RawScreen $second,
    ) {
    }
}
