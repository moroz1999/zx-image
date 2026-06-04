<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Stellar;

use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginServices;

final readonly class StellarLoader
{
    public function loadFrom(
        PluginInput $input,
        PluginGeometry $geometry,
        PluginServices $services,
    ): ?DualRawScreen {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
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

        $pixelsArray = $this->generatePixelsArray($geometry->width, $geometry->height);
        return new DualRawScreen(
            new RawScreen($pixelsArray, $attr0),
            new RawScreen($pixelsArray, $attr1),
        );
    }

    /**
     * @return list<int>
     */
    private function generatePixelsArray(int $width, int $height): array
    {
        $pixelsArray = [];
        for ($i = 0; $i < $width * $height / 8; $i++) {
            $pixelsArray[] = 0x0F;
        }
        return $pixelsArray;
    }
}
