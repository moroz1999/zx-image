<?php

namespace ZxImage;

abstract class ConverterFilter
{
    abstract public function apply($image, $srcImage = false);
}
