<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;

class Sam3 implements PluginInterface
{
    use PluginConfigTrait;

    private const int PALETTE_LENGTH = 4;
    private const int BITS_PER_PIXEL = 2;
    private const int BRIGHTNESS_MULTIPLIER = 36;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->width = 512;
        $this->height = 384;
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

        $pixelByteCount = (int)($this->width * $this->height / 2 / (8 / self::BITS_PER_PIXEL));
        $pixelsBytes = $reader->readBytes($pixelByteCount);
        $paletteBytes = $reader->readBytes(self::PALETTE_LENGTH);

        $colors = $this->parseSamPalette($paletteBytes, $config->r11, $config->r12, $config->r13, $config->r21, $config->r22, $config->r23, $config->r31, $config->r32, $config->r33);
        $pixelsData = $this->parsePixels($pixelsBytes);

        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $colorNumber) {
                if ($colorNumber === 1) {
                    $colorNumber = 2;
                } elseif ($colorNumber === 2) {
                    $colorNumber = 1;
                }
                $color = $colors[$colorNumber];
                imagesetpixel($image, $x, $y * 2, $color);
                imagesetpixel($image, $x, $y * 2 + 1, $color);
            }
        }

        $image = $this->imageProcessor->applyBorder($image, $this->border, $colorTable, $this->width, $this->height, $this->borderWidth, $this->borderHeight, $this->usesBorder);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        $image = $this->imageProcessor->rotate($image, $this->rotation);

        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
    }

    private function parsePixels(array $pixelsBytes): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($pixelsBytes as $byte) {
            $pixelsData[$y][$x] = ($byte >> 6) & 0x03;
            $x++;
            $pixelsData[$y][$x] = ($byte >> 4) & 0x03;
            $x++;
            $pixelsData[$y][$x] = ($byte >> 2) & 0x03;
            $x++;
            $pixelsData[$y][$x] = $byte & 0x03;
            $x++;
            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }

    private function parseSamPalette(array $paletteBytes, int $r11, int $r12, int $r13, int $r21, int $r22, int $r23, int $r31, int $r32, int $r33): array
    {
        $m = self::BRIGHTNESS_MULTIPLIER;
        $colors = [];
        foreach ($paletteBytes as $byte) {
            $bright = ($byte >> 3) & 1;
            $r = ((($byte >> 5) & 1) * 4 + (($byte >> 1) & 1) * 2 + $bright) * $m;
            $g = ((($byte >> 6) & 1) * 4 + (($byte >> 2) & 1) * 2 + $bright) * $m;
            $b = ((($byte >> 4) & 1) * 4 + ($byte & 1) * 2 + $bright) * $m;

            $red = (int)round(($r * $r11 + $g * $r12 + $b * $r13) / 0xFF);
            $green = (int)round(($r * $r21 + $g * $r22 + $b * $r23) / 0xFF);
            $blue = (int)round(($r * $r31 + $g * $r32 + $b * $r33) / 0xFF);

            $colors[] = $red * 0x010000 + $green * 0x0100 + $blue;
        }
        return $colors;
    }
}
