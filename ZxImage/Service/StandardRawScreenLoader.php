<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;

final readonly class StandardRawScreenLoader
{
    private const int PIXELS_SIZE = 6144;

    public function load(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?RawScreen
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $pixelsBytes = $reader->readBytes(self::PIXELS_SIZE);
        $attributesBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $attributesBytes[] = $byte;
        }

        return new RawScreen($pixelsBytes, $attributesBytes);
    }
}
