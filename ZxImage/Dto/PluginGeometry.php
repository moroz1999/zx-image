<?php

declare(strict_types=1);

namespace ZxImage\Dto;

final readonly class PluginGeometry
{
    public function __construct(
        public int $width = 256,
        public int $height = 192,
        public int $attributeWidth = 8,
        public int $attributeHeight = 8,
        public int $borderWidth = 32,
        public int $borderHeight = 24,
        public bool $usesBorder = true,
        public ?int $requiredFileSize = null,
    ) {
    }

    public function withAttributeHeight(int $attributeHeight): self
    {
        return new self(
            $this->width,
            $this->height,
            $this->attributeWidth,
            $attributeHeight,
            $this->borderWidth,
            $this->borderHeight,
            $this->usesBorder,
            $this->requiredFileSize,
        );
    }

    public function withDimensions(int $width, int $height): self
    {
        return new self(
            $width,
            $height,
            $this->attributeWidth,
            $this->attributeHeight,
            $this->borderWidth,
            $this->borderHeight,
            $this->usesBorder,
            $this->requiredFileSize,
        );
    }

    public function withRequiredFileSize(?int $requiredFileSize): self
    {
        return new self(
            $this->width,
            $this->height,
            $this->attributeWidth,
            $this->attributeHeight,
            $this->borderWidth,
            $this->borderHeight,
            $this->usesBorder,
            $requiredFileSize,
        );
    }

    public function toRenderGeometry(): RenderGeometry
    {
        return new RenderGeometry(
            $this->width,
            $this->height,
            $this->borderWidth,
            $this->borderHeight,
            $this->usesBorder,
        );
    }
}
