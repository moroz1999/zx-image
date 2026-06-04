<?php

declare(strict_types=1);

namespace ZxImage;

use ZxImage\Dto\ConversionHashInput;
use ZxImage\Dto\ConversionRequest;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Enum\FilterType;
use ZxImage\Enum\GigascreenMode;
use ZxImage\Enum\PalettePreset;
use ZxImage\Service\ConversionCacheManager;
use ZxImage\Service\ConversionHashBuilder;
use ZxImage\Service\ConversionService;

/** @psalm-api */
class Converter
{
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

    public function __construct(
        private ConversionService $conversionService = new ConversionService(),
        private ConversionHashBuilder $hashBuilder = new ConversionHashBuilder(),
        private ConversionCacheManager $cacheManager = new ConversionCacheManager(),
    ) {
        $this->palette = PalettePreset::Srgb->paletteString();
    }

    public function setSourceFileContents(string $sourceFileContents): self
    {
        $this->sourceFileContents = $sourceFileContents;
        $this->resetResultMime();
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

    public function setGigascreenMode(string $mode): self
    {
        $gigascreenMode = GigascreenMode::tryFrom($mode);
        if ($gigascreenMode !== null) {
            $this->gigascreenMode = $gigascreenMode->value;
            $this->resetResultMime();
        }
        return $this;
    }

    public function setRotation(int $rotation): self
    {
        if (in_array($rotation, [0, 90, 180, 270], true)) {
            $this->rotation = $rotation;
            $this->resetResultMime();
        }
        return $this;
    }

    public function addPreFilter(string $type): self
    {
        $filterType = FilterType::tryFrom($type);
        if ($filterType !== null) {
            $this->preFilters[] = $filterType->value;
            $this->resetResultMime();
        }
        return $this;
    }

    public function addPostFilter(string $type): self
    {
        $filterType = FilterType::tryFrom($type);
        if ($filterType !== null) {
            $this->postFilters[] = $filterType->value;
            $this->resetResultMime();
        }
        return $this;
    }

    public function setPalette(string $palette): self
    {
        $palettePreset = PalettePreset::tryFrom($palette) ?? PalettePreset::Srgb;
        $this->palette = $palettePreset->paletteString();
        $this->resetResultMime();
        return $this;
    }

    public function setBorder(?int $border = null): self
    {
        if ($border === null || ($border >= 0 && $border < 8)) {
            $this->border = $border;
            $this->resetResultMime();
        }
        return $this;
    }

    public function setZoom(float $zoom): self
    {
        if ($zoom >= 0.25 && $zoom <= 4) {
            $this->zoom = $zoom;
            $this->resetResultMime();
        }
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        $this->resetResultMime();
        return $this;
    }

    public function setPath(string $path): self
    {
        $this->sourceFilePath = $path;
        $this->resetResultMime();
        return $this;
    }

    public function getResultMime(): ?string
    {
        if ($this->cacheManager->isEnabled()) {
            return $this->cacheManager->getMime($this->getHash());
        }

        return $this->resultMime;
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
        return $this->hashBuilder->build($this->createHashInput());
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
        $renderedImage = $this->conversionService->convert($this->createConversionRequest());
        if ($renderedImage === null) {
            $this->resultMime = null;
            return null;
        }

        $this->resultMime = $renderedImage->mime;
        return $renderedImage->binary;
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

    private function createConversionRequest(): ConversionRequest
    {
        return new ConversionRequest(
            $this->type,
            new PluginInput($this->sourceFilePath, $this->sourceFileContents),
            new RenderSettings(
                $this->border,
                $this->zoom,
                $this->rotation,
                $this->gigascreenMode,
                $this->palette,
                $this->preFilters,
                $this->postFilters,
            ),
        );
    }

    private function resetResultMime(): void
    {
        $this->resultMime = null;
    }
}
