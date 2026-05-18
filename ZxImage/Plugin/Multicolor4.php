<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;

class Multicolor4 implements PluginInterface
{
    use StandardConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->attributeHeight = 4;
        $this->requiredFileSize = 7680;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }
}
