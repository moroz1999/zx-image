<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Timexhr;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class TimexhrLoader
{
    private const int PLANE_SIZE = 6144;

    public function loadFrom(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?TimexhrData
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $pixelsArray1 = $reader->readBytes(self::PLANE_SIZE);
        $pixelsArray2 = $reader->readBytes(self::PLANE_SIZE);
        $attributeByte = $reader->readByte() ?? 0;

        $pixelsBytes = [];
        for ($i = 0; $i < self::PLANE_SIZE; $i++) {
            $pixelsBytes[] = $pixelsArray1[$i];
            $pixelsBytes[] = $pixelsArray2[$i];
        }

        return new TimexhrData($pixelsBytes, $attributeByte);
    }
}
