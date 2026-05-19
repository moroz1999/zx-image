<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Specscii;

use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginRuntime;

final readonly class SpecsciiLoader
{
    public function load(PluginRuntime $runtime): ?RawScreen
    {
        $reader = $runtime->fileLoader->openSource(
            $runtime->sourceFilePath,
            $runtime->sourceFileContents,
            null,
        );
        if ($reader === null) {
            return null;
        }

        $tokens = [];
        while (($byte = $reader->readByte()) !== null) {
            $tokens[] = $byte;
        }
        return (new SpecsciiTokenParser())->parse($tokens);
    }
}
