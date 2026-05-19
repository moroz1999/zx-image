<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Plugin\Sxg\SxgPaletteParser;
use ZxImage\Plugin\Sxg\SxgPixelParser;
use ZxImage\Service\PluginRuntime;

class Sxg implements PluginInterface
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

        $firstByte = $reader->readByte();
        $signature = $reader->readString(3);
        if ($firstByte !== 127 || $signature !== 'SXG') {
            return null;
        }

        $reader->readByte(); // version
        $reader->readByte(); // background
        $reader->readByte(); // packed
        $sxgFormat = $reader->readByte() ?? self::FORMAT_256;
        $this->runtime->width = $reader->readWord() ?? $this->runtime->width;
        $this->runtime->height = $reader->readWord() ?? $this->runtime->height;
        $paletteShift = $reader->readWord() ?? 0;
        $pixelsShift = $reader->readWord() ?? 0;

        $reader->readBytes($paletteShift - 2);

        $paletteLength = (int)(($pixelsShift - $paletteShift + 2) / 2);
        $paletteWords = $reader->readWords($paletteLength);

        $pixelsBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $pixelsBytes[] = $byte;
        }

        $colors = (new SxgPaletteParser())->parse($paletteWords);
        $pixelsData = (new SxgPixelParser())->parse($pixelsBytes, $sxgFormat, $this->runtime->width);

        $image = imagecreatetruecolor($this->runtime->width, $this->runtime->height);
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                if (isset($colors[$pixel])) {
                    imagesetpixel($image, $x, $y, $colors[$pixel]);
                }
            }
        }

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
