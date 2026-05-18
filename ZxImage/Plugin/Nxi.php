<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;

class Nxi implements PluginInterface
{
    use PluginConfigTrait;

    protected const int PALETTE_LENGTH = 256;

    private const array RGB3_TO_RGB8 = [
        0 => 0,
        1 => 36,
        2 => 73,
        3 => 109,
        4 => 146,
        5 => 182,
        6 => 219,
        7 => 255,
    ];

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->requiredFileSize = 49664;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    public function convert(): ?string
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, $this->requiredFileSize);
        if ($reader === null) {
            return null;
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        $config = $colorTable->config;

        $paletteBytes = [];
        for ($i = 0; $i < static::PALETTE_LENGTH; $i++) {
            $paletteBytes[] = [$reader->readByte() ?? 0, $reader->readByte() ?? 0];
        }
        $pixelsBytes = $reader->readBytes($this->width * $this->height);

        $colors = $this->parseNxiPalette($paletteBytes, $config->r11, $config->r12, $config->r13, $config->r21, $config->r22, $config->r23, $config->r31, $config->r32, $config->r33);
        $pixelsData = $this->parseLinearPixels($pixelsBytes);

        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                imagesetpixel($image, $x, $y, $colors[$pixel]);
            }
        }

        $image = $this->imageProcessor->applyBorder($image, $this->border, $colorTable, $this->width, $this->height, $this->borderWidth, $this->borderHeight, $this->usesBorder);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        $image = $this->imageProcessor->rotate($image, $this->rotation);

        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
    }

    protected function parseLinearPixels(array $pixelsBytes): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($pixelsBytes as $byte) {
            $pixelsData[$y][$x] = $byte;
            $x++;
            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }

    protected function parseNxiPalette(array $paletteBytes, int $r11, int $r12, int $r13, int $r21, int $r22, int $r23, int $r31, int $r32, int $r33): array
    {
        $colors = [];
        foreach ($paletteBytes as [$byte1, $byte2]) {
            $r = self::RGB3_TO_RGB8[($byte1 >> 5) & 0x07];
            $g = self::RGB3_TO_RGB8[($byte1 >> 2) & 0x07];
            $b = self::RGB3_TO_RGB8[(($byte1 & 0x03) << 1) | ($byte2 & 0x01)];

            $red = (int)round(($r * $r11 + $g * $r12 + $b * $r13) / 0xFF);
            $green = (int)round(($r * $r21 + $g * $r22 + $b * $r23) / 0xFF);
            $blue = (int)round(($r * $r31 + $g * $r32 + $b * $r33) / 0xFF);

            $colors[] = $red * 0x010000 + $green * 0x0100 + $blue;
        }
        return $colors;
    }
}
