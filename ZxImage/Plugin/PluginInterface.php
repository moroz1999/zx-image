<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

interface PluginInterface
{
    public function convert(): ?string;

    public function setBorder(?int $border = null): void;

    public function setZoom(float $zoom): void;

    public function setRotation(int $rotation): void;

    public function setGigascreenMode(string $mode): void;

    public function setPalette(string $palette): void;

    public function setPreFilters(array $filters): void;

    public function setPostFilters(array $filters): void;

    public function setBasePath(string $basePath): void;

    public function getResultMime(): ?string;
}
