<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class ChrdData
{
    public function __construct(
        public int $colorType,
        public ParsedScreen $screen1,
        public ParsedScreen $screen2,
    ) {}
}
