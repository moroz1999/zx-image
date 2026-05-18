<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;

class SsxRaw implements PluginInterface
{
    use PluginConfigTrait;

    private const int REQUIRED_FILE_SIZE = 98304;
    private const int BRIGHTNESS_MULTIPLIER = 36;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->requiredFileSize = self::REQUIRED_FILE_SIZE;
        $this->width = 512;
        $this->height = 192;
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

        $pixelsBytes = $reader->readBytes(self::REQUIRED_FILE_SIZE);
        $pixelsData = $this->parsePixels($pixelsBytes);

        $m = self::BRIGHTNESS_MULTIPLIER;
        $image = imagecreatetruecolor($this->width, $this->height * 2);

        foreach ($pixelsData as $rowY => $row) {
            $y = $rowY * 2;
            foreach ($row as $x => $clutItem) {
                $bright = ($clutItem >> 3) & 1;
                $r = ((($clutItem >> 5) & 1) * 4 + (($clutItem >> 1) & 1) * 2 + $bright) * $m;
                $g = ((($clutItem >> 6) & 1) * 4 + (($clutItem >> 2) & 1) * 2 + $bright) * $m;
                $b = ((($clutItem >> 4) & 1) * 4 + ($clutItem & 1) * 2 + $bright) * $m;

                $red = (int)round(($r * $config->r11 + $g * $config->r12 + $b * $config->r13) / 0xFF);
                $green = (int)round(($r * $config->r21 + $g * $config->r22 + $b * $config->r23) / 0xFF);
                $blue = (int)round(($r * $config->r31 + $g * $config->r32 + $b * $config->r33) / 0xFF);

                $rgb = $red * 0x010000 + $green * 0x0100 + $blue;
                imagesetpixel($image, $x, $y, $rgb);
                imagesetpixel($image, $x, $y + 1, $rgb);
            }
        }

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
        foreach ($pixelsBytes as $pixel) {
            $pixelsData[$y][$x] = $pixel;
            $x++;
            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }
}
