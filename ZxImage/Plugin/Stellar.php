<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginRuntime;

class Stellar implements PluginInterface
{
    private PluginRuntime $runtime;
    private GigascreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = 3072;
        $this->runtime->attributeHeight = 4;
        $this->pipeline = new GigascreenPipeline();
    }

    public function convert(): ?string
    {
        return $this->pipeline->convertWithDefaultRendering($this->runtime, fn(): ?DualRawScreen => $this->loadBits());
    }

    private function loadBits(): ?DualRawScreen
    {
        $reader = $this->runtime->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            $this->runtime->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $attr0 = [];
        $attr1 = [];
        while (
            ($b0 = $reader->readByte()) !== null
            && ($b1 = $reader->readByte()) !== null
            && ($b2 = $reader->readByte()) !== null
            && ($b3 = $reader->readByte()) !== null
        ) {
            $attr0[] = $b0;
            $attr0[] = $b1;
            $attr1[] = $b2;
            $attr1[] = $b3;
        }

        $pixelsArray = $this->generatePixelsArray();
        return new DualRawScreen(
            new RawScreen($pixelsArray, $attr0),
            new RawScreen($pixelsArray, $attr1),
        );
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

    private function generatePixelsArray(): array
    {
        $pixelsArray = [];
        for ($i = 0; $i < $this->runtime->width * $this->runtime->height / 8; $i++) {
            $pixelsArray[] = 0x0F;
        }
        return $pixelsArray;
    }
}
