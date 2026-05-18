<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;

class Zxevo implements PluginInterface
{
    use PluginConfigTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->width = 320;
        $this->height = 200;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    public function convert(): ?string
    {
        if ($this->sourceFilePath === null || !file_exists($this->sourceFilePath)) {
            return null;
        }

        $sizes = getimagesize($this->sourceFilePath);
        if ($sizes !== false) {
            $this->width = $sizes[0];
            $this->height = $sizes[1];
        }

        $gdObject = imagecreatefrombmp($this->sourceFilePath);
        if ($gdObject === false) {
            return null;
        }

        $colorsAmount = imagecolorstotal($gdObject);
        if ($colorsAmount > 16 || $colorsAmount === 0) {
            return null;
        }

        $image = $this->adjustImage($gdObject);

        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
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

        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }
}
