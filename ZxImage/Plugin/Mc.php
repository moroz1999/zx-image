<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;

class Mc implements PluginInterface
{
    use StandardConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->attributeHeight = 1;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    protected function parseScreen(RawScreen $rawScreen): ParsedScreen
    {
        $linearMapper = static fn(int $y): int => $y;
        $attributes = (new AttributeParser($this->width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($this->width))->parse($rawScreen->pixelsBytes, $linearMapper);
        return new ParsedScreen($pixelsData, $attributes);
    }
}
