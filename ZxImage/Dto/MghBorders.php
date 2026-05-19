<?php

declare(strict_types=1);

namespace ZxImage\Dto;

readonly class MghBorders
{
    public function __construct(
        public ?int $border1,
        public ?int $border2,
    ) {}
}
