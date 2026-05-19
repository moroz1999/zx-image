<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Monochrome;

use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginRuntime;

final readonly class MonochromeLoader
{
    private const int PIXELS_SIZE = 6144;

    public function load(PluginRuntime $runtime): ?RawScreen
    {
        $reader = $runtime->fileLoader->openSource(
            $runtime->sourceFilePath,
            $runtime->sourceFileContents,
            $runtime->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }
        return new RawScreen($reader->readBytes(self::PIXELS_SIZE), []);
    }
}
