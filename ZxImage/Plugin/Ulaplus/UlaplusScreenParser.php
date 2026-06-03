<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Ulaplus;

use ZxImage\Dto\PaletteConfig;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\PixelParser;

final readonly class UlaplusScreenParser
{
    public function parse(RawScreen $rawScreen, int $width, PaletteConfig $paletteConfig): ParsedScreen
    {
        $attributes = (new UlaplusAttributeParser())->parse($rawScreen->attributesBytes, $width);
        $pixelsData = (new PixelParser($width))->parse($rawScreen->pixelsBytes);
        $colorOverrides = (new UlaplusPaletteParser())->parse($rawScreen->borderBytes, $paletteConfig);

        return new ParsedScreen($pixelsData, $attributes, $colorOverrides);
    }
}
