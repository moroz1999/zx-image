<?php

declare(strict_types=1);

namespace ZxImage;

use ZxImage\Dto\ConversionHashInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Enum\FilterType;
use ZxImage\Enum\GigascreenMode;
use ZxImage\Enum\PalettePreset;
use ZxImage\Service\ConversionCacheManager;
use ZxImage\Service\ConversionHashBuilder;
use ZxImage\Service\OutputRenderer;
use ZxImage\Service\PluginFactory;

class Converter
{
    protected ?string $hash = null;
    protected string $gigascreenMode = 'mix';
    protected string $sourceFileContents = '';
    protected string $sourceFilePath = '';
    protected string $type = 'standard';
    protected ?int $border = null;
    protected float $zoom = 1;
    protected int $rotation = 0;
    protected ?string $resultMime = null;
    /** @var list<string> */
    protected array $preFilters = [];
    /** @var list<string> */
    protected array $postFilters = [];

    protected string $palette = '';
    private ConversionCacheManager $cacheManager;

    public function __construct()
    {
        $this->palette = PalettePreset::Srgb->paletteString();
        $this->cacheManager = new ConversionCacheManager();
    }

    public function setSourceFileContents(string $sourceFileContents): self
    {
        $this->sourceFileContents = $sourceFileContents;
        return $this;
    }

    public function setCacheEnabled(bool $cacheEnabled): self
    {
        $this->cacheManager->setEnabled($cacheEnabled);
        return $this;
    }

    public function setCachePath(string $cachePath): self
    {
        $this->cacheManager->setPath($cachePath);
        return $this;
    }

    public function setCacheExpirationLimit(int $cacheExpirationLimit): self
    {
        $this->cacheManager->setExpirationLimit($cacheExpirationLimit);
        return $this;
    }

    public function setCacheDeletionAmount(int $cacheDeletionAmount): self
    {
        $this->cacheManager->setDeletionAmount($cacheDeletionAmount);
        return $this;
    }

    public function setCacheDeletionPeriod(int $cacheDeletionPeriod): self
    {
        $this->cacheManager->setDeletionPeriod($cacheDeletionPeriod);
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
        if ($border === null || ($border >= 0 && $border < 8)) {
            $this->border = $border;
        }
        return $this;
    }

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
        if ($this->cacheManager->isEnabled()) {
            $resultMime = $this->cacheManager->getMime($this->getHash());
        } else {
            if ($this->resultMime === null) {
                $this->generateBinary();
            }
            if ($this->resultMime !== null) {
                $resultMime = $this->resultMime;
            }
        }
        return $resultMime;
    }

    public function setCacheFileName(string $cacheFileName): void
    {
        $this->cacheManager->setFileName($cacheFileName);
    }

    public function getCacheFileName(): string
    {
        return $this->cacheManager->getFileName($this->getHash());
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
        if (!$this->cacheManager->isEnabled()) {
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
        return $this->cacheManager->loadOrGenerate(
            $this->getHash(),
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
