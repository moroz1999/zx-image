<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Atmega;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class AtmegaLoader
{
    private const int PIXEL_PAGE_SIZE = 8000;
    private const int FILE_SIZE_WITH_GAPS = 32896;
    private const int GAP_SIZE = 192;
    private const int TRAILER_SIZE = 21;
    private const array PALETTE_BYTES = [
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

    public function loadFrom(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?AtmegaData
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $pixelsArray = [];
        if ($reader->getSize() === self::FILE_SIZE_WITH_GAPS) {
            for ($page = 0; $page < 4; $page++) {
                $pixelsArray = array_merge($pixelsArray, $reader->readBytes(self::PIXEL_PAGE_SIZE));
                $reader->readBytes(self::GAP_SIZE);
            }
        } else {
            $pixelsArray = $reader->readBytes(self::PIXEL_PAGE_SIZE * 4);
        }

        $reader->readBytes(self::TRAILER_SIZE);
        return new AtmegaData($pixelsArray, self::PALETTE_BYTES);
    }
}
