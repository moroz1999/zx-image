<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

trait Sam
{
    protected function loadBits(): ?array
    {
        $pixelsArray = [];
        $paletteArray = [];
        if ($this->makeHandle()) {
            $divisor = (8 / $this->bitPerPixel) / $this->pixelRatio;
            $pixelByteCount = (int)($this->width * $this->height / $divisor);
            $pixelsArray = $this->readBytes($pixelByteCount);
            $paletteArray = $this->readBytes($this->paletteLength);
            return [
                'pixelsArray' => $pixelsArray,
                'paletteArray' => $paletteArray,
            ];
        }
        return null;
    }

    protected function parseScreen($data): array
    {
        $parsedData = [];
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseSamPalette($data['paletteArray']);
        return $parsedData;
    }

    protected function parseSamPalette(array $paletteArray): array
    {
        $m = 36;
        $paletteData = [];
        foreach ($paletteArray as $byte) {
            $bright = ($byte >> 3) & 1;
            $r = ((($byte >> 5) & 1) * 4 + (($byte >> 1) & 1) * 2 + $bright) * $m;
            $g = ((($byte >> 6) & 1) * 4 + (($byte >> 2) & 1) * 2 + $bright) * $m;
            $b = ((($byte >> 4) & 1) * 4 + ($byte & 1) * 2 + $bright) * $m;

            $redChannel = (int)round(
                ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
            );
            $greenChannel = (int)round(
                ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
            );
            $blueChannel = (int)round(
                ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
            );

            $paletteData[] = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }
        return $paletteData;
    }
}