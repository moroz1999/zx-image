<?php

declare(strict_types=1);

namespace ZxImage\Filter;

abstract class Filter
{
    abstract public function apply($image, $srcImage = false);
}
