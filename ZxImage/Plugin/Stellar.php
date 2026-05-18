<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\RawScreen;

class Stellar implements PluginInterface
{
    use GigascreenConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->requiredFileSize = 3072;
        $this->attributeHeight = 4;
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

        $pixelsArray = $this->generatePixelsArray();
        return new DualRawScreen(
            new RawScreen($pixelsArray, $attr0),
            new RawScreen($pixelsArray, $attr1),
        );
    }

    private function generatePixelsArray(): array
    {
        $pixelsArray = [];
        for ($i = 0; $i < $this->width * $this->height / 8; $i++) {
            $pixelsArray[] = 0x0F;
        }
        return $pixelsArray;
    }
}
