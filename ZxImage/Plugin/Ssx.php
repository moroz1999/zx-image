<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;

class Ssx implements PluginInterface
{
    use PluginConfigTrait;

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

    public function convert(): ?string
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, null);
        if ($reader === null) {
            return null;
        }

        $fileSize = $reader->getSize();

        if ($fileSize === 6928) {
            $this->converter->setType('standard');
        } elseif ($fileSize === 12304) {
            $this->converter->setType('mc');
        } elseif ($fileSize === 24580) {
            $this->converter->setType('sam3');
        } elseif ($fileSize === 24592) {
            $this->converter->setType('sam4');
        } elseif ($fileSize === 98304) {
            $this->converter->setType('ssxRaw');
        }

        $binary = $this->converter->getBinary();
        if ($binary !== null) {
            $this->resultMime = $this->converter->getResultMime();
        }
        return $binary;
    }
}
