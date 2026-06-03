<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Enum\PluginType;
use ZxImage\Plugin\FramePluginInterface;

final readonly class PluginFactory
{
    public function create(
        string $type,
        ?string $sourceFilePath,
        ?string $sourceFileContents,
    ): ?FramePluginInterface {
        $pluginType = PluginType::tryFrom($type);
        if ($pluginType === null) {
            return null;
        }

        $pluginClassName = $pluginType->pluginClassName();
        return new $pluginClassName($sourceFilePath, $sourceFileContents);
    }
}
