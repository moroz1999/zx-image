<?php

namespace ZxImage\Filter;

abstract class Filter
{
    abstract public function apply($image, $srcImage = false);
}
