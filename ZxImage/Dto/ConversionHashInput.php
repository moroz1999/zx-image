<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class ConversionHashInput
{
    /**
     * @param string[] $preFilters
     * @param string[] $postFilters
     */
    public function __construct(
        public string $sourceFileContents,
        public string $sourceFilePath,
        public string $type,
        public string $gigascreenMode,
        public ?int $border,
        public string $palette,
        public float $zoom,
        public array $preFilters,
        public array $postFilters,
        public int $rotation,
    ) {
    }
}
