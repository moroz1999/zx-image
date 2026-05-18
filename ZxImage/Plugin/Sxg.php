<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;

class Sxg implements PluginInterface
{
    use PluginConfigTrait;

    private const int FORMAT_256 = 2;
    private const int FORMAT_16 = 1;

    private const array LEVEL_TABLE = [
        0 => 0,
        1 => 10,
        2 => 21,
        3 => 31,
        4 => 42,
        5 => 53,
        6 => 63,
        7 => 74,
        8 => 85,
        9 => 95,
        10 => 106,
        11 => 117,
        12 => 127,
        13 => 138,
        14 => 149,
        15 => 159,
        16 => 170,
        17 => 181,
        18 => 191,
        19 => 202,
        20 => 213,
        21 => 223,
        22 => 234,
        23 => 245,
        24 => 255,
    ];

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
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, null);
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
        $this->width = $reader->readWord() ?? $this->width;
        $this->height = $reader->readWord() ?? $this->height;
        $paletteShift = $reader->readWord() ?? 0;
        $pixelsShift = $reader->readWord() ?? 0;

        $reader->readBytes($paletteShift - 2);

        $paletteLength = (int)(($pixelsShift - $paletteShift + 2) / 2);
        $paletteWords = $reader->readWords($paletteLength);

        $pixelsBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $pixelsBytes[] = $byte;
        }

        $colors = $this->parseSxgPalette($paletteWords, $sxgFormat);
        $pixelsData = $this->parsePixels($pixelsBytes, $sxgFormat);

        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                if (isset($colors[$pixel])) {
                    imagesetpixel($image, $x, $y, $colors[$pixel]);
                }
            }
        }

        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        $image = $this->imageProcessor->rotate($image, $this->rotation);

        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
    }

    private function parsePixels(array $pixelsBytes, int $format): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];

        if ($format === self::FORMAT_16) {
            foreach ($pixelsBytes as $byte) {
                $pixelsData[$y][$x] = ($byte >> 4) & 0x0F;
                $x++;
                $pixelsData[$y][$x] = $byte & 0x0F;
                $x++;
                if ($x >= $this->width) {
                    $x = 0;
                    $y++;
                }
            }
        } else {
            foreach ($pixelsBytes as $pixel) {
                $pixelsData[$y][$x] = $pixel;
                $x++;
                if ($x >= $this->width) {
                    $x = 0;
                    $y++;
                }
            }
        }
        return $pixelsData;
    }

    private function parseSxgPalette(array $words, int $format): array
    {
        $colors = [];
        foreach ($words as $word) {
            if (($word >> 15) === 0) {
                $colorIdx = ($word >> 10) & 0x1F;
                $r = self::LEVEL_TABLE[$colorIdx] ?? reset(self::LEVEL_TABLE);
                $colorIdx = ($word >> 5) & 0x1F;
                $g = self::LEVEL_TABLE[$colorIdx] ?? reset(self::LEVEL_TABLE);
                $colorIdx = $word & 0x1F;
                $b = self::LEVEL_TABLE[$colorIdx] ?? reset(self::LEVEL_TABLE);
            } else {
                $r = (($word >> 10) & 0x1F) << 3;
                $g = (($word >> 5) & 0x1F) << 3;
                $b = ($word & 0x1F) << 3;
            }
            $colors[] = $r * 0x010000 + $g * 0x0100 + $b;
        }
        return $colors;
    }
}
