<?php

declare(strict_types=1);

namespace ZxImage\Plugin\S81;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\CharacterScreenBuilder;
use ZxImage\Service\PluginServices;

final readonly class S81Loader
{
    private const int DEFAULT_ATTRIBUTE = 0x38;
    private const int WIDTH_IN_CHARACTERS = 32;

    public function loadFrom(PluginInput $input, PluginGeometry $geometry, PluginServices $services): ?RawScreen
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $tokens = [];
        while (($byte = $reader->readByte()) !== null) {
            $tokens[] = $byte;
        }

        return (new CharacterScreenBuilder())->buildRawScreenFromTokens(
            $tokens,
            fn(int $token): array => Zx81FontData::getChar($token),
            self::DEFAULT_ATTRIBUTE,
            self::WIDTH_IN_CHARACTERS,
        );
    }
}
