<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Ulaplus;

use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginRuntime;

final readonly class UlaplusLoader
{
    private const int PIXELS_SIZE = 6144;
    private const int ATTRIBUTES_SIZE = 768;
    private const int PALETTE_SIZE = 64;

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

        return new RawScreen(
            $reader->readBytes(self::PIXELS_SIZE),
            $reader->readBytes(self::ATTRIBUTES_SIZE),
            $reader->readBytes(self::PALETTE_SIZE),
        );
    }
}
