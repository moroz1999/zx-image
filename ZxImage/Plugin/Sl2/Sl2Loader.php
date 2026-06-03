<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sl2;

use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class Sl2Loader
{
    private const int HEADER_SIZE = 128;
    private const int DATA_SIZE = 49152;

    public function loadFrom(PluginInput $input, int $pixelCount, PluginServices $services): ?Sl2Data
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            null,
        );
        if ($reader === null) {
            return null;
        }

        if ($reader->getSize() === self::DATA_SIZE + self::HEADER_SIZE) {
            $reader->seek(self::HEADER_SIZE);
        }

        return new Sl2Data($reader->readBytes($pixelCount));
    }
}
