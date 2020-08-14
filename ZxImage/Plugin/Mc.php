<?php

namespace ZxImage\Plugin;

class Mc extends Standard
{
    protected int $attributeHeight = 1;
    protected ?int $fileSize = null;

    protected function calculateZXY($y)
    {
        $result = $y;
        return $result;
    }
}
