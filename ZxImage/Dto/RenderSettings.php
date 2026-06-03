<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class RenderSettings
{
    /**
     * @param string[] $preFilters
     * @param string[] $postFilters
     */
    public function __construct(
        public ?int $border = null,
        public float $zoom = 1.0,
        public int $rotation = 0,
        public string $gigascreenMode = 'mix',
        public string $paletteString = '',
        public array $preFilters = [],
        public array $postFilters = [],
    ) {
    }

    public function withBorder(?int $border): self
    {
        return new self(
            $border,
            $this->zoom,
            $this->rotation,
            $this->gigascreenMode,
            $this->paletteString,
            $this->preFilters,
            $this->postFilters,
        );
    }
}
