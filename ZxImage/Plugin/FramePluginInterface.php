<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Dto\FrameSet;
use ZxImage\Dto\RenderSettings;

/** @psalm-api */
interface FramePluginInterface
{
    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
    );

    public function configure(RenderSettings $settings): void;

    public function convertFrames(): ?FrameSet;
}
