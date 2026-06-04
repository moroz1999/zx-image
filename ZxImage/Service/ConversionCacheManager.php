<?php

declare(strict_types=1);

namespace ZxImage\Service;

final class ConversionCacheManager
{
    private string $cachePath = '';
    private ?string $cacheFileName = null;
    private bool $cacheEnabled = false;

    public function __construct(
        private readonly ConversionCache $cache = new ConversionCache(),
    ) {
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

    public function setFileName(string $cacheFileName): void
    {
        $this->cacheFileName = $cacheFileName;
    }

    public function getFileName(?string $hash): string
    {
        if ($this->cacheFileName !== null) {
            return $this->cacheFileName;
        }

        return $this->cachePath . ($hash ?? '');
    }

    public function getMime(?string $hash): ?string
    {
        return $this->cache->getMime($this->getFileName($hash));
    }

    /**
     * @param callable(): ?string $binaryGenerator
     */
    public function loadOrGenerate(?string $hash, callable $binaryGenerator): ?string
    {
        return $this->cache->loadOrGenerate($this->getFileName($hash), $binaryGenerator);
    }
}
