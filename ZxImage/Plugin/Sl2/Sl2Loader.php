<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sl2;

use ZxImage\Dto\IndexedPaletteEntry;
use ZxImage\Dto\PluginInput;
use ZxImage\Service\BitReader;
use ZxImage\Service\PluginServices;

final readonly class Sl2Loader
{
    private const int HEADER_SIZE = 128;
    private const int LEGACY_WIDTH = 256;
    private const int LEGACY_HEIGHT = 192;
    private const int LAYER2_HEIGHT = 256;
    private const int LAYER2_8_BIT_WIDTH = 320;
    private const int LAYER2_4_BIT_WIDTH = 640;
    private const int LEGACY_DATA_SIZE = self::LEGACY_WIDTH * self::LEGACY_HEIGHT;
    private const int LAYER2_DATA_SIZE = self::LAYER2_8_BIT_WIDTH * self::LAYER2_HEIGHT;
    private const int PALETTE_8_BIT_ENTRY_SIZE = 1;
    private const int PALETTE_9_BIT_ENTRY_SIZE = 2;
    private const int PALETTE_4_BIT_LENGTH = 16;
    private const int PALETTE_8_BIT_LENGTH = 256;

    public function loadFrom(PluginInput $input, PluginServices $services): ?Sl2Data
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            null,
        );
        if ($reader === null) {
            return null;
        }

        $fileSize = $reader->getSize();
        if ($fileSize === self::LEGACY_DATA_SIZE + self::HEADER_SIZE) {
            $reader->seek(self::HEADER_SIZE);
            return $this->loadScreen(
                $reader,
                self::LEGACY_WIDTH,
                self::LEGACY_HEIGHT,
                self::PALETTE_8_BIT_LENGTH,
                false,
                0,
            );
        }

        return match ($fileSize) {
            self::LEGACY_DATA_SIZE => $this->loadScreen(
                $reader,
                self::LEGACY_WIDTH,
                self::LEGACY_HEIGHT,
                self::PALETTE_8_BIT_LENGTH,
                false,
                0,
            ),
            self::LEGACY_DATA_SIZE + self::PALETTE_8_BIT_LENGTH => $this->loadScreen(
                $reader,
                self::LEGACY_WIDTH,
                self::LEGACY_HEIGHT,
                self::PALETTE_8_BIT_LENGTH,
                false,
                self::PALETTE_8_BIT_ENTRY_SIZE,
            ),
            self::LEGACY_DATA_SIZE + self::PALETTE_8_BIT_LENGTH * self::PALETTE_9_BIT_ENTRY_SIZE => $this->loadScreen(
                $reader,
                self::LEGACY_WIDTH,
                self::LEGACY_HEIGHT,
                self::PALETTE_8_BIT_LENGTH,
                false,
                self::PALETTE_9_BIT_ENTRY_SIZE,
            ),
            self::LAYER2_DATA_SIZE => $this->loadScreen(
                $reader,
                self::LAYER2_8_BIT_WIDTH,
                self::LAYER2_HEIGHT,
                self::PALETTE_8_BIT_LENGTH,
                false,
                0,
            ),
            self::LAYER2_DATA_SIZE + self::PALETTE_8_BIT_LENGTH => $this->loadScreen(
                $reader,
                self::LAYER2_8_BIT_WIDTH,
                self::LAYER2_HEIGHT,
                self::PALETTE_8_BIT_LENGTH,
                false,
                self::PALETTE_8_BIT_ENTRY_SIZE,
            ),
            self::LAYER2_DATA_SIZE + self::PALETTE_8_BIT_LENGTH * self::PALETTE_9_BIT_ENTRY_SIZE => $this->loadScreen(
                $reader,
                self::LAYER2_8_BIT_WIDTH,
                self::LAYER2_HEIGHT,
                self::PALETTE_8_BIT_LENGTH,
                false,
                self::PALETTE_9_BIT_ENTRY_SIZE,
            ),
            self::LAYER2_DATA_SIZE + self::PALETTE_4_BIT_LENGTH => $this->loadScreen(
                $reader,
                self::LAYER2_4_BIT_WIDTH,
                self::LAYER2_HEIGHT,
                self::PALETTE_4_BIT_LENGTH,
                true,
                self::PALETTE_8_BIT_ENTRY_SIZE,
            ),
            self::LAYER2_DATA_SIZE + self::PALETTE_4_BIT_LENGTH * self::PALETTE_9_BIT_ENTRY_SIZE => $this->loadScreen(
                $reader,
                self::LAYER2_4_BIT_WIDTH,
                self::LAYER2_HEIGHT,
                self::PALETTE_4_BIT_LENGTH,
                true,
                self::PALETTE_9_BIT_ENTRY_SIZE,
            ),
            default => null,
        };
    }

    /**
     * @param positive-int $width
     * @param positive-int $height
     */
    private function loadScreen(
        BitReader $reader,
        int $width,
        int $height,
        int $paletteLength,
        bool $hasPackedPixels,
        int $paletteEntrySize,
    ): Sl2Data {
        $pixelBytes = $reader->readBytes(intdiv($width * $height, $hasPackedPixels ? 2 : 1));
        $pixels = $height === self::LAYER2_HEIGHT
            ? $this->parseLayer2Pixels($pixelBytes, $width, $height, $hasPackedPixels)
            : $pixelBytes;
        $paletteEntries = $paletteEntrySize === 0
            ? (new Sl2DefaultPaletteFactory())->create($paletteLength)
            : $this->readPalette($reader, $paletteLength, $paletteEntrySize);

        return new Sl2Data($pixels, $paletteEntries, $width, $height);
    }

    /**
     * @return list<IndexedPaletteEntry>
     */
    private function readPalette(BitReader $reader, int $paletteLength, int $paletteEntrySize): array
    {
        $paletteEntries = [];
        for ($index = 0; $index < $paletteLength; $index++) {
            $byte1 = $reader->readByte() ?? 0;
            if ($paletteEntrySize === self::PALETTE_9_BIT_ENTRY_SIZE) {
                $byte2 = $reader->readByte() ?? 0;
            } else {
                $byte2 = ($byte1 & 0x03) === 0 ? 0 : 1;
            }
            $paletteEntries[] = new IndexedPaletteEntry($byte1, $byte2);
        }

        return $paletteEntries;
    }

    /**
     * @param list<int> $pixelBytes
     * @param positive-int $width
     * @param positive-int $height
     *
     * @return list<int>
     */
    private function parseLayer2Pixels(array $pixelBytes, int $width, int $height, bool $hasPackedPixels): array
    {
        $columnCount = intdiv($width, $hasPackedPixels ? 2 : 1);
        $pixels = [];
        for ($y = 0; $y < $height; $y++) {
            for ($column = 0; $column < $columnCount; $column++) {
                $byte = $pixelBytes[$column * $height + $y];
                if ($hasPackedPixels) {
                    $pixels[] = ($byte >> 4) & 0x0F;
                    $pixels[] = $byte & 0x0F;
                } else {
                    $pixels[] = $byte;
                }
            }
        }

        return $pixels;
    }
}
