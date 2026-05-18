<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;

class Tricolor implements PluginInterface
{
    use PluginConfigTrait;

    private const int REQUIRED_FILE_SIZE = 18432;

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
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, self::REQUIRED_FILE_SIZE);
        if ($reader === null) {
            return null;
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);

        $screenColors = [
            [10, 0],
            [12, 0],
            [9, 0],
        ];

        $screens = [];
        for ($i = 0; $i < 3; $i++) {
            $pixelsBytes = $reader->readBytes(6144);
            [$inkKey, $paperKey] = $screenColors[$i];
            $pixelsData = (new PixelParser($this->width))->parse($pixelsBytes);
            $screens[] = new ParsedScreen($pixelsData, $this->buildFlatAttributeMap($inkKey, $paperKey));
        }

        if ($this->gigascreenMode === 'flicker') {
            $gifImages = [];
            foreach ($screens as $screen) {
                $gifImages[] = $this->imageEncoder->toPaletteGif($this->renderScreen($screen, $colorTable));
            }
            $this->resultMime = 'image/gif';
            return $this->imageEncoder->toAnimatedGif($gifImages, [2, 2, 2]);
        }

        $resources = [];
        foreach ($screens as $screen) {
            $resources[] = $this->renderScreen($screen, $colorTable);
        }

        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($this->buildMixedImage($resources));
    }

    private function renderScreen(ParsedScreen $screen, ColorTable $colorTable): GdImage
    {
        $renderer = new PixelRenderer();
        $image = $renderer->render(
            $screen,
            false,
            $colorTable->colors,
            $this->width,
            $this->height,
            $this->attributeWidth,
            $this->attributeHeight,
        );
        $image = $this->imageProcessor->applyBorder($image, $this->border, $colorTable, $this->width, $this->height, $this->borderWidth, $this->borderHeight, $this->usesBorder);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        return $this->imageProcessor->rotate($image, $this->rotation);
    }

    private function buildFlatAttributeMap(int $inkKey, int $paperKey): AttributeMap
    {
        $rows = (int)($this->height / 8);
        $cols = (int)($this->width / 8);
        return new AttributeMap(
            array_fill(0, $rows, array_fill(0, $cols, $inkKey)),
            array_fill(0, $rows, array_fill(0, $cols, $paperKey)),
            [],
        );
    }

    private function buildMixedImage(array $resources): GdImage
    {
        $first = reset($resources);
        $width = imagesx($first);
        $height = imagesy($first);
        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $overall = 0;
                foreach ($resources as $resource) {
                    $overall += imagecolorat($resource, $x, $y);
                }
                imagesetpixel($image, $x, $y, $overall);
            }
        }
        return $image;
    }
}
