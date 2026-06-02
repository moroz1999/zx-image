<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Specscii;

use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginServices;

final readonly class SpecsciiLoader
{
    public function loadFrom(PluginInput $input, PluginServices $services): ?RawScreen
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
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
