<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\SamCoupeScreenRenderer;

class Sam3 implements PluginInterface
{
    private const int PALETTE_LENGTH = 4;
    private const int BITS_PER_PIXEL = 2;

    private PluginRuntime $runtime;
    private SamCoupeScreenRenderer $renderer;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->width = 512;
        $this->runtime->height = 384;
        $this->renderer = new SamCoupeScreenRenderer();
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

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);

        $pixelByteCount = (int)($this->runtime->width * $this->runtime->height / 2 / (8 / self::BITS_PER_PIXEL));
        $pixelsBytes = $reader->readBytes($pixelByteCount);
        $paletteBytes = $reader->readBytes(self::PALETTE_LENGTH);

        $image = $this->renderer->render(
            $pixelsBytes,
            $paletteBytes,
            self::BITS_PER_PIXEL,
            true,
            true,
            $colorTable,
            $this->runtime,
        );

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
