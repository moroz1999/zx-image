<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\PixelParser;

class Monochrome implements PluginInterface
{
    use PluginConfigTrait;

    private const int INK_KEY = 15;
    private const int PAPER_KEY = 8;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->requiredFileSize = 6144;
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

        $renderer = new Standard\PixelRenderer();
        $image = $renderer->render(
            $parsedScreen,
            false,
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
        $image = $this->imageProcessor->rotate($image, $this->rotation);

        $this->resultMime = 'image/gif';
        return $this->imageEncoder->toGif($image);
    }

    private function loadBits(): ?RawScreen
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, $this->requiredFileSize);
        if ($reader === null) {
            return null;
        }
        return new RawScreen($reader->readBytes(6144), []);
    }

    private function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $pixelsData = (new PixelParser($this->width))->parse($rawScreen->pixelsBytes);
        $attributes = $this->buildMonochromeAttributeMap();
        return new ParsedScreen($pixelsData, $attributes);
    }

    private function buildMonochromeAttributeMap(): AttributeMap
    {
        $rows = (int)($this->height / 8);
        $cols = (int)($this->width / 8);
        $inkMap = array_fill(0, $rows, array_fill(0, $cols, self::INK_KEY));
        $paperMap = array_fill(0, $rows, array_fill(0, $cols, self::PAPER_KEY));
        return new AttributeMap($inkMap, $paperMap, []);
    }
}
