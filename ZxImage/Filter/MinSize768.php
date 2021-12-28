<?php

declare(strict_types=1);

namespace ZxImage\Filter;

class MinSize768 extends MinSize384
{
    protected $canvasWidth = 768;
    protected $canvasHeight = 576;
}