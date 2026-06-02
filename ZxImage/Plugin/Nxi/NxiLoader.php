<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Nxi;

use ZxImage\Dto\IndexedPaletteEntry;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class NxiLoader
{
    private const int PALETTE_LENGTH = 256;

    public function loadFrom(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?NxiData
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $paletteEntries = [];
        for ($i = 0; $i < self::PALETTE_LENGTH; $i++) {
            $paletteEntries[] = new IndexedPaletteEntry($reader->readByte() ?? 0, $reader->readByte() ?? 0);
        }

        $pixelsBytes = $reader->readBytes($geometry->width * $geometry->height);
        return new NxiData($paletteEntries, $pixelsBytes);
    }
}
