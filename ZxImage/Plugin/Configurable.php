<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

interface Configurable
{
    public function __construct(string $sourceFilePath);

    /**
     * @return string|null
     */
    public function convert();

    /**
     * @return void
     */
    public function setBorder(int $border = null);

    /**
     * @return void
     */
    public function setPalette(string $palette);

    /**
     * @return void
     */
    public function setZoom(float $zoom);

    /**
     * @return void
     */
    public function setRotation(int $rotation);

    /**
     * @return void
     */
    public function setGigascreenMode(string $mode);
}