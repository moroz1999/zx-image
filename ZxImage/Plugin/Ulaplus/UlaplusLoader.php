<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Ulaplus;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginServices;

final readonly class UlaplusLoader
{
    private const int PIXELS_SIZE = 6144;
    private const int ATTRIBUTES_SIZE = 768;
    private const int PALETTE_SIZE = 64;

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

        return new RawScreen(
            $reader->readBytes(self::PIXELS_SIZE),
            $reader->readBytes(self::ATTRIBUTES_SIZE),
            $reader->readBytes(self::PALETTE_SIZE),
        );
    }
}
