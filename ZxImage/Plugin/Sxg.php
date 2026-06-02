<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Plugin\Sxg\SxgPaletteParser;
use ZxImage\Plugin\Sxg\SxgPixelParser;
use ZxImage\Service\PluginServices;

class Sxg implements FramePluginInterface
{
    private const int FORMAT_256 = 8;

    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry(usesBorder: false);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        $reader = $this->services->fileLoader->openSource(
            $this->input->sourceFilePath,
            $this->input->sourceFileContents,
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
        $this->geometry = $this->geometry->withDimensions(
            $reader->readWord() ?? $this->geometry->width,
            $reader->readWord() ?? $this->geometry->height,
        );
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
        $pixelsData = (new SxgPixelParser())->parse($pixelsBytes, $sxgFormat, $this->geometry->width);

        $image = imagecreatetruecolor($this->geometry->width, $this->geometry->height);
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                if (isset($colors[$pixel])) {
                    imagesetpixel($image, $x, $y, $colors[$pixel]);
                }
            }
        }

        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }
}
