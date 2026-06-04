<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class ConversionRequest
{
    public function __construct(
        public string $type,
        public PluginInput $input,
        public RenderSettings $renderSettings,
    ) {
    }
}
