<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Service\PluginRuntime;

class Ssx implements PluginInterface
{
    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
    }

    public function convert(): ?string
    {
        $reader = $this->runtime->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            null,
        );
        if ($reader === null) {
            return null;
        }

        $converter = $this->runtime->converter;
        if ($converter === null) {
            return null;
        }

        $fileSize = $reader->getSize();

        if ($fileSize === 6928) {
            $converter->setType('standard');
        } elseif ($fileSize === 12304) {
            $converter->setType('mc');
        } elseif ($fileSize === 24580) {
            $converter->setType('sam3');
        } elseif ($fileSize === 24592) {
            $converter->setType('sam4');
        } elseif ($fileSize === 98304) {
            $converter->setType('ssxRaw');
        }

        $binary = $converter->getBinary();
        if ($binary !== null) {
            $this->runtime->resultMime = $converter->getResultMime();
        }
        return $binary;
    }

    public function setBorder(?int $border = null): void
    {
        $this->runtime->setBorder($border);
    }

    public function setZoom(float $zoom): void
    {
        $this->runtime->setZoom($zoom);
    }

    public function setRotation(int $rotation): void
    {
        $this->runtime->setRotation($rotation);
    }

    public function setGigascreenMode(string $mode): void
    {
        $this->runtime->setGigascreenMode($mode);
    }

    public function setPalette(string $palette): void
    {
        $this->runtime->setPalette($palette);
    }

    public function setPreFilters(array $filters): void
    {
        $this->runtime->setPreFilters($filters);
    }

    public function setPostFilters(array $filters): void
    {
        $this->runtime->setPostFilters($filters);
    }

    public function setBasePath(string $basePath): void
    {
        $this->runtime->setBasePath($basePath);
    }

    public function getResultMime(): ?string
    {
        return $this->runtime->getResultMime();
    }
}
