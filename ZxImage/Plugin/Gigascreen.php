<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\RawScreen;

class Gigascreen implements PluginInterface
{
    use GigascreenConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->requiredFileSize = 13824;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    protected function loadBits(): ?DualRawScreen
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, $this->requiredFileSize);
        if ($reader === null) {
            return null;
        }

        $first = new RawScreen($reader->readBytes(6144), $reader->readBytes(768));
        $second = new RawScreen($reader->readBytes(6144), $reader->readBytes(768));
        return new DualRawScreen($first, $second);
    }
}
