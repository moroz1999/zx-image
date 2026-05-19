<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\ColorTable;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\BscBorderRenderer;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Standard\PixelRenderer;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Bmc4 implements PluginInterface
{
    private PluginRuntime $runtime;
    private StandardScreenPipeline $pipeline;

    private const int STANDARD_ATTRIBUTES_LENGTH = 768;
    private const int FILE_SIZE = 11904;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->runtime = new PluginRuntime($sourceFilePath, $sourceFileContents, $converter);
        $this->runtime->borderWidth = 64;
        $this->runtime->borderHeight = 56;
        $this->runtime->attributeHeight = 4;
        $this->runtime->requiredFileSize = self::FILE_SIZE;
        $this->pipeline = new StandardScreenPipeline();
    }

    public function convert(): ?string
    {
        return $this->pipeline->convertUsing(
            $this->runtime,
            fn(): ?RawScreen => $this->loadBits(),
            fn(RawScreen $rawScreen): ParsedScreen => $this->parseScreen($rawScreen),
            fn(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage => $this->renderImage(
                $parsedScreen,
                $colorTable,
                $flashedImage,
            ),
        );
    }

    private function loadBits(): ?RawScreen
    {
        $reader = $this->runtime->fileLoader->openSource(
            $this->runtime->sourceFilePath,
            $this->runtime->sourceFileContents,
            $this->runtime->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $pixelsBytes = $reader->readBytes(6144);
        $firstAttributesBytes = $reader->readBytes(self::STANDARD_ATTRIBUTES_LENGTH);
        $secondAttributesBytes = $reader->readBytes(self::STANDARD_ATTRIBUTES_LENGTH);

        $attributesBytes = $this->interleaveAttributes($firstAttributesBytes, $secondAttributesBytes);

        $borderBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $borderBytes[] = $byte;
        }

        return new RawScreen($pixelsBytes, $attributesBytes, $borderBytes);
    }

    private function interleaveAttributes(array $firstBytes, array $secondBytes): array
    {
        $result = [];
        for ($row = 0; $row < 24; $row++) {
            for ($col = 0; $col < 32; $col++) {
                $result[] = $firstBytes[$row * 32 + $col];
            }
            for ($col = 0; $col < 32; $col++) {
                $result[] = $secondBytes[$row * 32 + $col];
            }
        }
        return $result;
    }

    private function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = (new AttributeParser($this->runtime->width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($this->runtime->width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes, [], $rawScreen->borderBytes);
    }

    private function renderImage(ParsedScreen $parsedScreen, ColorTable $colorTable, bool $flashedImage): GdImage
    {
        $image = (new PixelRenderer())->render(
            $parsedScreen,
            $flashedImage,
            $colorTable->colors,
            $this->runtime->width,
            $this->runtime->height,
            $this->runtime->attributeWidth,
            $this->runtime->attributeHeight,
        );

        $image = (new BscBorderRenderer())->render(
            $image,
            $parsedScreen,
            $colorTable,
            $this->runtime->border,
            $this->runtime->width,
            $this->runtime->height,
            $this->runtime->borderWidth,
            $this->runtime->borderHeight,
        );
        $image = $this->runtime->imageProcessor->resize($image, $this->runtime->zoom, $this->runtime->preFilters, $this->runtime->postFilters);
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
