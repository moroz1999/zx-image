<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Service\PluginRuntime;

class Zxevo implements PluginInterface
{
    private PluginRuntime $runtime;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->width = 320;
        $this->runtime->height = 200;
    }

    public function convert(): ?string
    {
        if ($this->runtime->sourceFilePath === null || !file_exists($this->runtime->sourceFilePath)) {
            return null;
        }

        $sizes = getimagesize($this->runtime->sourceFilePath);
        if ($sizes !== false) {
            $this->runtime->width = $sizes[0];
            $this->runtime->height = $sizes[1];
        }

        $gdObject = imagecreatefrombmp($this->runtime->sourceFilePath);
        if ($gdObject === false) {
            return null;
        }

        $colorsAmount = imagecolorstotal($gdObject);
        if ($colorsAmount > 16 || $colorsAmount === 0) {
            return null;
        }

        $image = $this->adjustImage($gdObject);

        $this->runtime->resultMime = 'image/png';
        return $this->runtime->imageEncoder->toPng($image);
    }

    private function adjustImage(GdImage $image): GdImage
    {
        $colorsAmount = imagecolorstotal($image);
        for ($i = 0; $i < $colorsAmount; $i++) {
            $color = imagecolorsforindex($image, $i);
            $color['red'] = (int)round($color['red'] / 85) * 85;
            $color['green'] = (int)round($color['green'] / 85) * 85;
            $color['blue'] = (int)round($color['blue'] / 85) * 85;
            imagecolorset($image, $i, $color['red'], $color['green'], $color['blue']);
        }

        $image = $this->runtime->imageProcessor->resize(
            $image,
            $this->runtime->zoom,
            $this->runtime->preFilters,
            $this->runtime->postFilters,
        );
        return $this->runtime->imageProcessor->rotate($image, $this->runtime->rotation);
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
