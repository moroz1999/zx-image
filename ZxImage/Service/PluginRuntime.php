<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Converter;

final class PluginRuntime
{
    public FileLoader $fileLoader;
    public PaletteService $paletteService;
    public ImageProcessor $imageProcessor;
    public ImageEncoder $imageEncoder;

    public ?int $border = null;
    public float $zoom = 1.0;
    public int $rotation = 0;
    public string $gigascreenMode = 'mix';
    /** @var string[] */
    public array $preFilters = [];
    /** @var string[] */
    public array $postFilters = [];
    public string $basePath = '';
    public string $paletteString = '';
    public ?string $resultMime = null;

    public int $width = 256;
    public int $height = 192;
    public int $attributeWidth = 8;
    public int $attributeHeight = 8;
    public int $borderWidth = 32;
    public int $borderHeight = 24;
    public bool $usesBorder = true;
    public ?int $requiredFileSize = null;

    public function __construct(
        public ?string $sourceFilePath = null,
        public ?string $sourceFileContents = null,
        public ?Converter $converter = null,
    ) {
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

    /**
     * @param string[] $filters
     */
    public function setPreFilters(array $filters): void
    {
        $this->preFilters = $filters;
    }

    /**
     * @param string[] $filters
     */
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
