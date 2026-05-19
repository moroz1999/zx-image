<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Attributes;

use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginRuntime;

final readonly class AttributesLoader
{
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

        $attributesBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $attributesBytes[] = $byte;
        }
        return new RawScreen([], $attributesBytes);
    }
}
