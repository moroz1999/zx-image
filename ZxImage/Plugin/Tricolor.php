<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Plugin\Tricolor\TricolorMixer;
use ZxImage\Service\PluginRuntime;
use ZxImage\Service\StandardScreenPipeline;

class Tricolor implements PluginInterface
{
    private const int REQUIRED_FILE_SIZE = 18432;

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
            self::REQUIRED_FILE_SIZE,
        );
        if ($reader === null) {
            return null;
        }

        $colorTable = $this->runtime->paletteService->buildColorTable($this->runtime->paletteString);
        $pipeline = new StandardScreenPipeline();

        $screenColors = [
            [10, 0],
            [12, 0],
            [9, 0],
        ];

        $screens = [];
        for ($i = 0; $i < 3; $i++) {
            $pixelsBytes = $reader->readBytes(6144);
            [$inkKey, $paperKey] = $screenColors[$i];
            $rows = (int)($this->runtime->height / 8);
            $cols = (int)($this->runtime->width / 8);
            $attributes = new AttributeMap(
                array_fill(0, $rows, array_fill(0, $cols, $inkKey)),
                array_fill(0, $rows, array_fill(0, $cols, $paperKey)),
                [],
            );
            $pixelsData = (new PixelParser($this->runtime->width))->parse($pixelsBytes);
            $screens[] = new ParsedScreen($pixelsData, $attributes);
        }

        if ($this->runtime->gigascreenMode === 'flicker') {
            $gifImages = [];
            foreach ($screens as $screen) {
                $image = $pipeline->renderImage($screen, $colorTable, false, $this->runtime);
                $gifImages[] = $this->runtime->imageEncoder->toPaletteGif($image);
            }
            $this->runtime->resultMime = 'image/gif';
            return $this->runtime->imageEncoder->toAnimatedGif($gifImages, [2, 2, 2]);
        }

        $resources = [];
        foreach ($screens as $screen) {
            $resources[] = $pipeline->renderImage($screen, $colorTable, false, $this->runtime);
        }

        $this->runtime->resultMime = 'image/png';
        return $this->runtime->imageEncoder->toPng((new TricolorMixer())->mix($resources));
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
