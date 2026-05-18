<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;

class Flash implements PluginInterface
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

    public function convert(): ?string
    {
        $rawScreen = $this->loadBits();
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        $parsedScreen = $this->parseScreen($rawScreen);
        $image = $this->renderImage($parsedScreen, $colorTable, false);

        $this->resultMime = 'image/gif';
        return $this->imageEncoder->toGif($image);
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

                if (isset($parsedScreen->attributes->flashMap[$mapY][$mapX])) {
                    $color = $pixel === 1
                        ? $colorTable->gigaColors[($inkKey << 4) | $paperKey]
                        : $colorTable->colors[0];
                } else {
                    $colorKey = $pixel === 1 ? $inkKey : $paperKey;
                    $color = $colorTable->colors[$colorKey];
                }

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
