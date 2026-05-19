<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Plugin\SsxRaw\SsxRawRenderer;
use ZxImage\Service\PluginRuntime;

class SsxRaw implements PluginInterface
{
    private const int REQUIRED_FILE_SIZE = 98304;

    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->requiredFileSize = self::REQUIRED_FILE_SIZE;
        $this->runtime->width = 512;
        $this->runtime->height = 192;
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
        $pixelsBytes = $reader->readBytes(self::REQUIRED_FILE_SIZE);

        $image = (new SsxRawRenderer())->render($pixelsBytes, $this->runtime->width, $this->runtime->height, $colorTable->config);

        $image = $this->runtime->imageProcessor->resize($image, $this->runtime->zoom, $this->runtime->preFilters, $this->runtime->postFilters);
        $image = $this->runtime->imageProcessor->rotate($image, $this->runtime->rotation);

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
