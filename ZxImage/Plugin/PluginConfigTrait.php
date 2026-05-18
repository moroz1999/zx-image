<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Service\FileLoader;
use ZxImage\Service\ImageEncoder;
use ZxImage\Service\ImageProcessor;
use ZxImage\Service\PaletteService;

trait PluginConfigTrait
{
    protected ?string $sourceFilePath = null;
    protected ?string $sourceFileContents = null;
    protected ?Converter $converter = null;

    protected FileLoader $fileLoader;
    protected PaletteService $paletteService;
    protected ImageProcessor $imageProcessor;
    protected ImageEncoder $imageEncoder;

    protected ?int $border = null;
    protected float $zoom = 1.0;
    protected int $rotation = 0;
    protected string $gigascreenMode = 'mix';
    protected array $preFilters = [];
    protected array $postFilters = [];
    protected string $basePath = '';
    protected string $paletteString = '';
    protected ?string $resultMime = null;

    protected int $width = 256;
    protected int $height = 192;
    protected int $attributeWidth = 8;
    protected int $attributeHeight = 8;
    protected int $borderWidth = 32;
    protected int $borderHeight = 24;
    protected bool $usesBorder = true;
    protected ?int $requiredFileSize = null;

    protected function initServices(): void
    {
        $this->fileLoader = new FileLoader();
        $this->paletteService = new PaletteService();
        $this->imageProcessor = new ImageProcessor();
        $this->imageEncoder = new ImageEncoder();
    }

    public function setBorder(?int $border = null): void
    {
        $this->border = $border;
    }

    public function setZoom(float $zoom): void
    {
        $this->zoom = $zoom;
    }

    public function setRotation(int $rotation): void
    {
        $this->rotation = $rotation;
    }

    public function setGigascreenMode(string $mode): void
    {
        if ($mode === 'flicker' || $mode === 'interlace2' || $mode === 'interlace1') {
            $this->gigascreenMode = $mode;
        }
    }

    public function setPalette(string $palette): void
    {
        $this->paletteString = $palette;
    }

    public function setPreFilters(array $filters): void
    {
        $this->preFilters = $filters;
    }

    public function setPostFilters(array $filters): void
    {
        $this->postFilters = $filters;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    public function getResultMime(): ?string
    {
        return $this->resultMime;
    }
}
