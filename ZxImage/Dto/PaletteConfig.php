<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class PaletteConfig
{
    public function __construct(
        public int $zz,
        public int $zn,
        public int $nn,
        public int $nb,
        public int $bb,
        public int $zb,
        public int $r11,
        public int $r12,
        public int $r13,
        public int $r21,
        public int $r22,
        public int $r23,
        public int $r31,
        public int $r32,
        public int $r33,
    ) {
    }
}
