<?php

declare(strict_types=1);

namespace ZxImage\Plugin\S80;

use ZxImage\Dto\RawScreen;
use ZxImage\Service\CharacterScreenBuilder;
use ZxImage\Service\PluginRuntime;

final readonly class S80Loader
{
    private const int DEFAULT_ATTRIBUTE = 0x38;
    private const int WIDTH_IN_CHARACTERS = 32;

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

        $tokens = [];
        while (($byte = $reader->readByte()) !== null) {
            $tokens[] = $byte;
        }

        return (new CharacterScreenBuilder())->buildRawScreenFromTokens(
            $tokens,
            fn(int $token): array => Zx80FontData::getChar($token),
            self::DEFAULT_ATTRIBUTE,
            self::WIDTH_IN_CHARACTERS,
        );
    }
}
