<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Service\IndexedScreenRenderer;
use ZxImage\Service\PluginRuntime;

class Nxi implements PluginInterface
{
    protected const int PALETTE_LENGTH = 256;

    private PluginRuntime $runtime;
    private IndexedScreenRenderer $renderer;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = 49664;
        $this->renderer = new IndexedScreenRenderer();
    }

    public function convert(): ?string
    {
        $reader = $this->runtime->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            $this->runtime->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);

        $paletteBytes = [];
        for ($i = 0; $i < static::PALETTE_LENGTH; $i++) {
            $paletteBytes[] = [$reader->readByte() ?? 0, $reader->readByte() ?? 0];
        }
        $pixelsBytes = $reader->readBytes($this->runtime->width * $this->runtime->height);

        $image = $this->renderer->render($pixelsBytes, $paletteBytes, $colorTable, $this->runtime);

        $this->runtime->resultMime = 'image/png';
        return $this->runtime->imageEncoder->toPng($image);
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
