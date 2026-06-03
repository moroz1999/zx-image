<?php

declare(strict_types=1);

namespace ZxImage\Plugin\SsxRaw;

use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class SsxRawLoader
{
    public function loadFrom(PluginInput $input, int $requiredFileSize, PluginServices $services): ?SsxRawData
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        return new SsxRawData($reader->readBytes($requiredFileSize));
    }
}
