<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Timexhrg;

use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginRuntime;

final readonly class TimexhrgLoader
{
    private const int PLANE_SIZE = 6144;

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

        $img1 = $reader->readBytes(self::PLANE_SIZE);
        $img2 = $reader->readBytes(self::PLANE_SIZE);
        $attr1 = [$reader->readByte() ?? 0];
        $img3 = $reader->readBytes(self::PLANE_SIZE);
        $img4 = $reader->readBytes(self::PLANE_SIZE);
        $attr2 = [$reader->readByte() ?? 0];

        $pixels1 = [];
        $pixels2 = [];
        for ($i = 0; $i < self::PLANE_SIZE; $i++) {
            $pixels1[] = $img1[$i];
            $pixels1[] = $img2[$i];
            $pixels2[] = $img3[$i];
            $pixels2[] = $img4[$i];
        }

        return new DualRawScreen(
            new RawScreen($pixels1, $attr1),
            new RawScreen($pixels2, $attr2),
        );
    }
}
