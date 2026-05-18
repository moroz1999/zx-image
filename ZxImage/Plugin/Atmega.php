<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;

class Atmega implements PluginInterface
{
    use PluginConfigTrait;

    private const int PIXEL_PAGE_SIZE = 8000;
    private const int FILE_SIZE_WITH_GAPS = 32896;

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
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, null);
        if ($reader === null) {
            return null;
        }

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        $config = $colorTable->config;
        $fileSize = $reader->getSize();

        $pixelsArray = [];
        if ($fileSize === self::FILE_SIZE_WITH_GAPS) {
            for ($page = 0; $page < 4; $page++) {
                $pixelsArray = array_merge($pixelsArray, $reader->readBytes(self::PIXEL_PAGE_SIZE));
                $reader->readBytes(192);
            }
        } else {
            $pixelsArray = $reader->readBytes(self::PIXEL_PAGE_SIZE * 4);
        }
        $reader->readBytes(21);

        $paletteBytes = [
            0b00000000,
            0b00000001,
            0b00000010,
            0b00000011,
            0b00010000,
            0b00010001,
            0b00010010,
            0b00010011,
            0b00000000,
            0b00100001,
            0b01000010,
            0b01100011,
            0b10010000,
            0b10110001,
            0b11010010,
            0b11110011,
        ];

        $colors = $this->parseAtmPalette($paletteBytes, $config->r11, $config->r12, $config->r13, $config->r21, $config->r22, $config->r23, $config->r31, $config->r32, $config->r33);
        $pixelsData = $this->parsePixels($pixelsArray);

        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $colorIndex) {
                $color = $colors[$colorIndex];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $image = $this->imageProcessor->applyBorder($image, $this->border, $colorTable, $this->width, $this->height, $this->borderWidth, $this->borderHeight, $this->usesBorder);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        $image = $this->imageProcessor->rotate($image, $this->rotation);

        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
    }

    private function parsePixels(array $pixelsArray): array
    {
        $x = 0;
        $y = 0;
        $length = 0;
        $block = 0;
        $pixelsData = [];

        foreach ($pixelsArray as $byte) {
            $length++;
            $pixelsData[$y][$x * 2] = ((($byte & 0x40) >> 3) | ($byte & 0x07));
            $pixelsData[$y][$x * 2 + 1] = ((($byte & 0x80) >> 4) | (($byte >> 3) & 0x07));

            $x = $x + 4;

            if ($x >= $this->width / 2) {
                $x = (int)floor($length / self::PIXEL_PAGE_SIZE);
                if ($block !== $x) {
                    $block = $x;
                    $y = 0;
                } else {
                    $y++;
                }
            }
        }
        return $pixelsData;
    }

    private function parseAtmPalette(array $paletteBytes, int $r11, int $r12, int $r13, int $r21, int $r22, int $r23, int $r31, int $r32, int $r33): array
    {
        $levels = [0, 0x55, 0xAA, 0xFF];
        $colors = [];

        foreach ($paletteBytes as $byte) {
            $rValue = (($byte >> 1) & 1) * 2 + (($byte >> 6) & 1);
            $gValue = (($byte >> 4) & 1) * 2 + (($byte >> 7) & 1);
            $bValue = ($byte & 1) * 2 + (($byte >> 5) & 1);

            $r = $levels[$rValue];
            $g = $levels[$gValue];
            $b = $levels[$bValue];

            $red = (int)round(($r * $r11 + $g * $r12 + $b * $r13) / 0xFF);
            $green = (int)round(($r * $r21 + $g * $r22 + $b * $r23) / 0xFF);
            $blue = (int)round(($r * $r31 + $g * $r32 + $b * $r33) / 0xFF);

            $colors[] = $red * 0x010000 + $green * 0x0100 + $blue;
        }
        return $colors;
    }
}
