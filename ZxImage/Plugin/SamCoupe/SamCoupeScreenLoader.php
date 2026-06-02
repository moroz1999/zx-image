<?php

declare(strict_types=1);

namespace ZxImage\Plugin\SamCoupe;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class SamCoupeScreenLoader
{
    public function loadFrom(
        PluginInput $input,
        PluginGeometry $geometry,
        PluginServices $services,
        int $bitsPerPixel,
        int $paletteLength,
        bool $doubleRows,
    ): ?SamCoupeScreenData {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $rowDivisor = $doubleRows ? 2 : 1;
        $pixelByteCount = (int)($geometry->width * $geometry->height / $rowDivisor / (8 / $bitsPerPixel));
        $pixelsBytes = $reader->readBytes($pixelByteCount);
        $paletteBytes = $reader->readBytes($paletteLength);

        return new SamCoupeScreenData($pixelsBytes, $paletteBytes);
    }
}
