<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Ssx;

use ZxImage\Dto\PluginInput;
use ZxImage\Plugin\FramePluginInterface;
use ZxImage\Plugin\Mc;
use ZxImage\Plugin\Sam3;
use ZxImage\Plugin\Sam4;
use ZxImage\Plugin\SsxRaw;
use ZxImage\Plugin\Standard;
use ZxImage\Service\PluginServices;

final readonly class SsxPluginResolver
{
    /**
     * @return class-string<FramePluginInterface>|null
     */
    public function resolveType(PluginInput $input, PluginServices $services): ?string
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            null,
        );
        if ($reader === null) {
            return null;
        }

        return match ($reader->getSize()) {
            6928 => Standard::class,
            12304 => Mc::class,
            24580 => Sam3::class,
            24592 => Sam4::class,
            98304 => SsxRaw::class,
            default => null,
        };
    }
}
