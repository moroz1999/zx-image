<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Gigascreen;

use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginServices;

final readonly class GigascreenLoader
{
    private const int PIXELS_SIZE = 6144;
    private const int ATTRIBUTES_SIZE = 768;

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

        $first = new RawScreen($reader->readBytes(self::PIXELS_SIZE), $reader->readBytes(self::ATTRIBUTES_SIZE));
        $second = new RawScreen($reader->readBytes(self::PIXELS_SIZE), $reader->readBytes(self::ATTRIBUTES_SIZE));
        return new DualRawScreen($first, $second);
    }
}
