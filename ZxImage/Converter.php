<?php

declare(strict_types=1);

namespace ZxImage;

use ZxImage\Dto\ConversionHashInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Enum\FilterType;
use ZxImage\Enum\GigascreenMode;
use ZxImage\Enum\PalettePreset;
use ZxImage\Service\ConversionCache;
use ZxImage\Service\ConversionHashBuilder;
use ZxImage\Service\OutputRenderer;
use ZxImage\Service\PluginFactory;

class Converter
{
    protected ?string $hash = null;
    protected array $colors = [];
    protected string $gigascreenMode = 'mix';
    protected string $cachePath;
    protected string $sourceFileContents = '';
    protected string $sourceFilePath = '';
    protected string $resultFilePath;
    protected int $cacheDeletionPeriod = 300; //start cache clearing every 5 minutes
    protected int $cacheDeletionAmount = 1000; //delete not more than 1000 images at once
    protected int $cacheExpirationLimit;
    protected string $type = 'standard';
    protected ?int $border = null;
    protected float $zoom = 1;
    protected int $rotation = 0;
    protected ?string $cacheFileName = null;
    protected bool $cacheEnabled = false;
    protected ?string $resultMime = null;
    protected string $basePath;
    protected array $preFilters = [];
    protected array $postFilters = [];

    protected string $palette = '';

    public function __construct()
    {
        $this->palette = PalettePreset::Srgb->paletteString();
        $this->cacheExpirationLimit = 60 * 60 * 24 * 30 * 1; //delete files older than 2 months
        $this->basePath = pathinfo((__FILE__), PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;
    }

    public function setSourceFileContents(string $sourceFileContents): self
    {
        $this->sourceFileContents = $sourceFileContents;
        return $this;
    }

    public function setCacheEnabled(bool $cacheEnabled): self
    {
        $this->cacheEnabled = $cacheEnabled;
        return $this;
    }

    public function setCachePath(string $cachePath): self
    {
        $this->cachePath = $cachePath . DIRECTORY_SEPARATOR;
        return $this;
    }

    public function setCacheExpirationLimit(int $cacheExpirationLimit): self
    {
        $this->cacheExpirationLimit = $cacheExpirationLimit;
        return $this;
    }

    public function setCacheDeletionAmount(int $cacheDeletionAmount): self
    {
        $this->cacheDeletionAmount = $cacheDeletionAmount;
        return $this;
    }

    public function setCacheDeletionPeriod(int $cacheDeletionPeriod): self
    {
        $this->cacheDeletionPeriod = $cacheDeletionPeriod;
        return $this;
    }

    public function setGigascreenMode(string $mode): self
    {
        $gigascreenMode = GigascreenMode::tryFrom($mode);
        if ($gigascreenMode !== null) {
            $this->gigascreenMode = $gigascreenMode->value;
        }
        return $this;
    }

    public function setRotation(int $rotation): self
    {
        if (in_array($rotation, [0, 90, 180, 270], true)) {
            $this->rotation = $rotation;
        }
        return $this;
    }

    public function addPreFilter(string $type): self
    {
        $filterType = FilterType::tryFrom($type);
        if ($filterType !== null) {
            $this->preFilters[] = $filterType->value;
        }
        return $this;
    }

    public function addPostFilter(string $type): self
    {
        $filterType = FilterType::tryFrom($type);
        if ($filterType !== null) {
            $this->postFilters[] = $filterType->value;
        }
        return $this;
    }

    public function setPalette(string $palette): self
    {
        $palettePreset = PalettePreset::tryFrom($palette) ?? PalettePreset::Srgb;
        $this->palette = $palettePreset->paletteString();
        return $this;
    }

    public function setBorder(?int $border = null): self
    {
        if (($border >= 0 && $border < 8) || $border === null) {
            $this->border = $border;
        }
        return $this;
    }

    /**
     * @param $zoom
     * @return $this
     */
    public function setZoom(float $zoom): self
    {
        if ($zoom >= 0.25 && $zoom <= 4) {
            $this->zoom = $zoom;
        }
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setPath(string $path): self
    {
        $this->sourceFilePath = $path;
        return $this;
    }

    public function getResultMime(): ?string
    {
        $resultMime = null;
        if ($this->cacheEnabled) {
            if ($resultFilePath = $this->getCacheFileName()) {
                $resultMime = (new ConversionCache())->getMime($resultFilePath);
            }
        } else {
            if (!$this->resultMime){
                $this->generateBinary();
            }
            if ($this->resultMime) {
                $resultMime = $this->resultMime;
            }
        }
        return $resultMime;
    }

    public function setCacheFileName(string $cacheFileName): void
    {
        $this->cacheFileName = $cacheFileName;
    }

    public function getCacheFileName(): string
    {
        if ($this->cacheFileName === null) {
            $parametersHash = $this->getHash();
            $this->cacheFileName = $this->cachePath . $parametersHash;
        }

        return $this->cacheFileName;
    }

    public function getHash(): ?string
    {
        if ($this->hash === null) {
            $this->hash = (new ConversionHashBuilder())->build($this->createHashInput());
        }
        return $this->hash;
    }

    public function getBinary(): ?string
    {
        if (!$this->cacheEnabled) {
            return $this->generateBinary();
        }

        return $this->generateCacheFile();
    }

    public function generateBinary(): ?string
    {
        $result = null;
        $plugin = (new PluginFactory())->create($this->type, $this->sourceFilePath, $this->sourceFileContents);
        if ($plugin !== null) {
            $plugin->configure(new RenderSettings(
                $this->border,
                $this->zoom,
                $this->rotation,
                $this->gigascreenMode,
                $this->palette,
                $this->preFilters,
                $this->postFilters,
                $this->basePath,
            ));

            $frameSet = $plugin->convertFrames();
            if ($frameSet !== null) {
                $renderedImage = (new OutputRenderer())->render($frameSet);
                $result = $renderedImage->binary;
                $this->resultMime = $renderedImage->mime;
            }
        }
        return $result;
    }

    public function generateCacheFile(): ?string
    {
        $resultFilePath = $this->getCacheFileName();

        return (new ConversionCache())->loadOrGenerate(
            $resultFilePath,
            fn(): ?string => $this->generateBinary(),
        );
    }

    private function createHashInput(): ConversionHashInput
    {
        return new ConversionHashInput(
            $this->sourceFileContents,
            $this->sourceFilePath,
            $this->type,
            $this->gigascreenMode,
            $this->border,
            $this->palette,
            $this->zoom,
            $this->preFilters,
            $this->postFilters,
            $this->rotation,
        );
    }
}
