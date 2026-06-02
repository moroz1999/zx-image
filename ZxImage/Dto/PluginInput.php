<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class PluginInput
{
    public function __construct(
        public ?string $sourceFilePath = null,
        public ?string $sourceFileContents = null,
    ) {
    }
}
