<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\GigascreenPipeline;
use ZxImage\Service\PluginRuntime;

class Lowresgs implements PluginInterface
{
    private PluginRuntime $runtime;
    private GigascreenPipeline $pipeline;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = 1628;
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

        $texture = [];
        $attr0 = [];
        $attr1 = [];
        $length = 0;
        while (($bin = $reader->readByte()) !== null) {
            if ($length >= 84 && $length < 92) {
                $texture[] = $bin;
            } elseif ($length >= 92 && $length < 92 + 768) {
                $attr0[] = $bin;
            } elseif ($length >= 92 + 768) {
                $attr1[] = $bin;
            }
            $length++;
        }

        $pixelsArray = $this->generatePixelsArray($texture);
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

    private function generatePixelsArray(array $texture): array
    {
        $pixelsArray = [];
        for ($third = 0; $third < 3; $third++) {
            $row = 0;
            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = $texture[$row];
                }
                $row++;
            }
        }
        return $pixelsArray;
    }
}
