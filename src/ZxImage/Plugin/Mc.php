<?php

namespace ZxImage\Plugin;


class Mc extends Standard
{
    protected $attributeHeight = 1;
    protected $fileSize = 12288;

    protected function calculateZXY($y)
    {
        $result = $y;
        return $result;
    }
}
