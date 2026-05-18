<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;

trait StandardConvertTrait
{
    use PluginConfigTrait;

    public function convert(): ?string
    {
        $rawScreen = $this->loadBits();
        if ($rawScreen === null) {
            return null;
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        $parsedScreen = $this->parseScreen($rawScreen);
        $hasFlash = count($parsedScreen->attributes->flashMap) > 0;

        if ($hasFlash) {
            return $this->buildFlashAnimation($parsedScreen, $colorTable);
        }

        $image = $this->renderImage($parsedScreen, $colorTable, false);
        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
    }

    protected function loadBits(): ?RawScreen
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, $this->requiredFileSize);
        if ($reader === null) {
            return null;
        }

        $pixelsBytes = $reader->readBytes(6144);
        $attributesBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $attributesBytes[] = $byte;
        }
        return new RawScreen($pixelsBytes, $attributesBytes);
    }

    protected function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = (new AttributeParser($this->width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($this->width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes);
    }

    protected function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $renderer = new PixelRenderer();
        $image = $renderer->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $this->width,
            $this->height,
            $this->attributeWidth,
            $this->attributeHeight,
        );

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

    private function buildFlashAnimation(ParsedScreen $parsedScreen, ColorTable $colorTable): string
    {
        $frame1 = $this->imageEncoder->toPaletteGif($this->renderImage($parsedScreen, $colorTable, false));
        $frame2 = $this->imageEncoder->toPaletteGif($this->renderImage($parsedScreen, $colorTable, true));
        $this->resultMime = 'image/gif';
        return $this->imageEncoder->toAnimatedGif([$frame1, $frame2], [32, 32]);
    }
}
