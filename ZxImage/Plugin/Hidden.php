<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;

class Hidden implements PluginInterface
{
    use StandardConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    protected function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $image = imagecreatetruecolor($this->width, $this->height);

        foreach ($parsedScreen->pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                $mapX = (int)($x / $this->attributeWidth);
                $mapY = (int)($y / $this->attributeHeight);

                $inkKey = $parsedScreen->attributes->inkMap[$mapY][$mapX];
                $paperKey = $parsedScreen->attributes->paperMap[$mapY][$mapX];
                $isHidden = false;

                if ($flashedImage && isset($parsedScreen->attributes->flashMap[$mapY][$mapX])) {
                    $colorKey = $pixel === 1 ? $paperKey : $inkKey;
                } elseif ($inkKey === $paperKey && $pixel === 1) {
                    $isHidden = true;
                    $colorKey = 0;
                } else {
                    $colorKey = $pixel === 1 ? $inkKey : $paperKey;
                }

                $color = $isHidden ? 0xFF8000 : $colorTable->colors[$colorKey];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $image = $this->imageProcessor->applyBorder(
            $image,
            $this->border,
            $colorTable,
            $this->width,
            $this->height,
            $this->borderWidth,
            $this->borderHeight,
            $this->usesBorder,
        );
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }
}
