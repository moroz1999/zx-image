<?php

namespace ZxImage\Plugin;

interface Configurable
{
    public function __construct(string $sourceFilePath);

    public function convert(): ?string;

    public function setBorder(int $border = null): void;

    public function setPalette(string $palette): void;

    public function setZoom(float $zoom): void;

    public function setRotation(int $rotation): void;

    public function setGigascreenMode(string $mode): void;
}