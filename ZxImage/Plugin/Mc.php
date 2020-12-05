<?php

namespace ZxImage\Plugin;

class Mc extends Standard
{
    protected int $attributeHeight = 1;
    protected ?int $strictFileSize;

    protected function calculateZXY(int $y): int
    {
        return $y;
    }
}
