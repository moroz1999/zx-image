<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Stellar;

use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginRuntime;

final readonly class StellarLoader
{
    public function load(PluginRuntime $runtime): ?DualRawScreen
    {
        $reader = $runtime->fileLoader->openSource(
            $runtime->sourceFilePath,
            $runtime->sourceFileContents,
            $runtime->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $attr0 = [];
        $attr1 = [];
        while (
            ($b0 = $reader->readByte()) !== null
            && ($b1 = $reader->readByte()) !== null
            && ($b2 = $reader->readByte()) !== null
            && ($b3 = $reader->readByte()) !== null
        ) {
            $attr0[] = $b0;
            $attr0[] = $b1;
            $attr1[] = $b2;
            $attr1[] = $b3;
        }

        $pixelsArray = $this->generatePixelsArray($runtime->width, $runtime->height);
        return new DualRawScreen(
            new RawScreen($pixelsArray, $attr0),
            new RawScreen($pixelsArray, $attr1),
        );
    }

    private function generatePixelsArray(int $width, int $height): array
    {
        $pixelsArray = [];
        for ($i = 0; $i < $width * $height / 8; $i++) {
            $pixelsArray[] = 0x0F;
        }
        return $pixelsArray;
    }
}
