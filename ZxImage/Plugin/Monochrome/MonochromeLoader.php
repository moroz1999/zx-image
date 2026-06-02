<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Monochrome;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginServices;

final readonly class MonochromeLoader
{
    private const int PIXELS_SIZE = 6144;

    public function loadFrom(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?RawScreen
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }
        return new RawScreen($reader->readBytes(self::PIXELS_SIZE), []);
    }
}
