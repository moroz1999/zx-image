<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;

class Attributes implements PluginInterface
{
    use StandardConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->requiredFileSize = 768;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    protected function loadBits(): ?RawScreen
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, $this->requiredFileSize);
        if ($reader === null) {
            return null;
        }

        $attributesBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $attributesBytes[] = $byte;
        }
        return new RawScreen([], $attributesBytes);
    }

    protected function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $attributes = (new AttributeParser($this->width))->parse($rawScreen->attributesBytes);
        return new ParsedScreen($this->generatePixelsData(), $attributes);
    }

    private function generatePixelsData(): array
    {
        $pixelsData = [];
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                $pixelsData[$y][$x] = ($x + $y) % 2;
            }
        }
        return $pixelsData;
    }
}
