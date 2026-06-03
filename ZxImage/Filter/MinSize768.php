<?php

declare(strict_types=1);

namespace ZxImage\Filter;

final class MinSize768 extends MinSize384
{
    protected int $canvasWidth = 768;
    protected int $canvasHeight = 576;
}
