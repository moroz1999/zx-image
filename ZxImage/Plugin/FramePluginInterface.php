<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Dto\FrameSet;
use ZxImage\Dto\RenderSettings;

interface FramePluginInterface
{
    public function configure(RenderSettings $settings): void;

    public function convertFrames(): ?FrameSet;
}
