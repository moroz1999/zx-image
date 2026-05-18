<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;

class Standard implements PluginInterface
{
    use StandardConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }
}
