<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

class Mc extends Standard
{
    /**
     * @var int
     */
    protected $attributeHeight = 1;
    /**
     * @var int|null
     */
    protected $strictFileSize;

    protected function calculateZXY(int $y): int
    {
        return $y;
    }
}
