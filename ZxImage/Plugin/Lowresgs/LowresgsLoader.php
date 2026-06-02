<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Lowresgs;

use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginServices;

final readonly class LowresgsLoader
{
    private const int TEXTURE_START = 84;
    private const int TEXTURE_END = 92;
    private const int ATTR_OFFSET = 92;
    private const int ATTR_LENGTH = 768;

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

        $texture = [];
        $attr0 = [];
        $attr1 = [];
        $length = 0;
        while (($bin = $reader->readByte()) !== null) {
            if ($length >= self::TEXTURE_START && $length < self::TEXTURE_END) {
                $texture[] = $bin;
            } elseif ($length >= self::ATTR_OFFSET && $length < self::ATTR_OFFSET + self::ATTR_LENGTH) {
                $attr0[] = $bin;
            } elseif ($length >= self::ATTR_OFFSET + self::ATTR_LENGTH) {
                $attr1[] = $bin;
            }
            $length++;
        }

        $pixelsArray = $this->generatePixelsArray($texture);
        return new DualRawScreen(
            new RawScreen($pixelsArray, $attr0),
            new RawScreen($pixelsArray, $attr1),
        );
    }

    private function generatePixelsArray(array $texture): array
    {
        $pixelsArray = [];
        for ($third = 0; $third < 3; $third++) {
            $row = 0;
            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = $texture[$row];
                }
                $row++;
            }
        }
        return $pixelsArray;
    }
}
