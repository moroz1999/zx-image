<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Grf;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class GrfLoader
{
    private const int PROFI_COLOR_FORMAT = 19;
    private const int PALETTE_SIZE = 16;
    private const int HEADER_REST_SIZE = 118;
    private const int PALETTE_PADDING_SIZE = 102;

    public function loadFrom(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?GrfData
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $geometry = $geometry->withDimensions(
            $reader->readWord() ?? $geometry->width,
            $reader->readWord() ?? $geometry->height,
        );
        $bitsPerPixel = $reader->readByte() ?? 4;
        $reader->readByte();
        $reader->readByte();
        $reader->readByte();
        $reader->readByte();
        $format = $reader->readByte() ?? 0;

        $paletteBytes = [];
        if ($format === self::PROFI_COLOR_FORMAT) {
            $paletteBytes = $reader->readBytes(self::PALETTE_SIZE);
            $reader->readBytes(self::PALETTE_PADDING_SIZE);
        } else {
            $reader->readBytes(self::HEADER_REST_SIZE);
        }

        $pixelsArray = [];
        $attributesArray = [];
        $length = (int)($geometry->width * $geometry->height / $bitsPerPixel);
        do {
            $pixel = $reader->readByte();
            $attribute = $reader->readByte();
            if ($pixel === null || $attribute === null) {
                return null;
            }
            $pixelsArray[] = $pixel;
            $attributesArray[] = $attribute;
        } while ($length = $length - 2);

        return new GrfData($geometry, $paletteBytes, $pixelsArray, $attributesArray);
    }
}
