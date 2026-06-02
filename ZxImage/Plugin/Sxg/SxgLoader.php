<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sxg;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class SxgLoader
{
    private const int SIGNATURE_PREFIX = 127;
    private const int FORMAT_256 = 8;

    public function loadFrom(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?SxgData
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $firstByte = $reader->readByte();
        $signature = $reader->readString(3);
        if ($firstByte !== self::SIGNATURE_PREFIX || $signature !== 'SXG') {
            return null;
        }

        $reader->readByte();
        $reader->readByte();
        $reader->readByte();
        $format = $reader->readByte() ?? self::FORMAT_256;
        $geometry = $geometry->withDimensions(
            $reader->readWord() ?? $geometry->width,
            $reader->readWord() ?? $geometry->height,
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

        return new SxgData($geometry, $format, $paletteWords, $pixelsBytes);
    }
}
