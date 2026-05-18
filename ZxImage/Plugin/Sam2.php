<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;

class Sam2 implements PluginInterface
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
        $zxyMapper = \Closure::fromCallable([$this, 'calculateZXY']);
        $attributes = (new AttributeParser($this->width))->parse($rawScreen->attributesBytes, $zxyMapper);
        $pixelsData = (new PixelParser($this->width))->parse($rawScreen->pixelsBytes);
        return new ParsedScreen($pixelsData, $attributes);
    }

    private function calculateZXY(int $y): int
    {
        $offset = 0;
        if ($y > 127) {
            $offset = 128;
            $y -= 128;
        } elseif ($y > 63) {
            $offset = 64;
            $y -= 64;
        }
        $rows = (int)($y / 8);
        $rests = $y - $rows * 8;
        return $offset + $rests * 8 + $rows;
    }
}
