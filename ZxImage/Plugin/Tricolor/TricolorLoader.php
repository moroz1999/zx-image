<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Tricolor;

use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class TricolorLoader
{
    private const int SCREEN_PIXELS_SIZE = 6144;
    private const int SCREEN_COUNT = 3;

    public function loadFrom(PluginInput $input, int $requiredFileSize, PluginServices $services): ?TricolorData
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $screenPixelsBytes = [];
        for ($screenIndex = 0; $screenIndex < self::SCREEN_COUNT; $screenIndex++) {
            $screenPixelsBytes[] = $reader->readBytes(self::SCREEN_PIXELS_SIZE);
        }

        return new TricolorData($screenPixelsBytes);
    }
}
