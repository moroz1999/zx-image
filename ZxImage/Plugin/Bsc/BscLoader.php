<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Bsc;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginServices;

final readonly class BscLoader
{
    private const int PIXELS_SIZE = 6144;
    private const int ATTRIBUTES_SIZE = 768;

    public function loadFrom(
        PluginInput $input,
        PluginGeometry $geometry,
        PluginServices $services,
    ): ?RawScreen
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
        $attributesBytes = $reader->readBytes(self::ATTRIBUTES_SIZE);
        $borderBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $borderBytes[] = $byte;
        }
        return new RawScreen($pixelsBytes, $attributesBytes, $borderBytes);
    }
}
