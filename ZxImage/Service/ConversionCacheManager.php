<?php

declare(strict_types=1);

namespace ZxImage\Service;

final class ConversionCacheManager
{
    private string $cachePath = '';
    private int $cacheDeletionPeriod = 300;
    private int $cacheDeletionAmount = 1000;
    private int $cacheExpirationLimit;
    private ?string $cacheFileName = null;
    private bool $cacheEnabled = false;

    public function __construct(
        private readonly ConversionCache $cache = new ConversionCache(),
    ) {
        $this->cacheExpirationLimit = 60 * 60 * 24 * 30;
    }

    public function setEnabled(bool $cacheEnabled): void
    {
        $this->cacheEnabled = $cacheEnabled;
    }

    public function isEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function setPath(string $cachePath): void
    {
        $this->cachePath = $cachePath . DIRECTORY_SEPARATOR;
    }

    public function setExpirationLimit(int $cacheExpirationLimit): void
    {
        $this->cacheExpirationLimit = $cacheExpirationLimit;
    }

    public function setDeletionAmount(int $cacheDeletionAmount): void
    {
        $this->cacheDeletionAmount = $cacheDeletionAmount;
    }

    public function setDeletionPeriod(int $cacheDeletionPeriod): void
    {
        $this->cacheDeletionPeriod = $cacheDeletionPeriod;
    }

    public function setFileName(string $cacheFileName): void
    {
        $this->cacheFileName = $cacheFileName;
    }

    public function getFileName(?string $hash): string
    {
        if ($this->cacheFileName === null) {
            $this->cacheFileName = $this->cachePath . $hash;
        }

        return $this->cacheFileName;
    }

    public function getMime(?string $hash): ?string
    {
        return $this->cache->getMime($this->getFileName($hash));
    }

    public function loadOrGenerate(?string $hash, callable $binaryGenerator): ?string
    {
        return $this->cache->loadOrGenerate($this->getFileName($hash), $binaryGenerator);
    }
}
