<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sca;

use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;

final readonly class ScaScreenParser
{
    public function parseScreen(RawScreen $rawScreen, int $width): ParsedScreen
    {
        $pixelParser = new PixelParser($width);
        $attributeParser = new AttributeParser($width);

        return new ParsedScreen(
            $pixelParser->parse($rawScreen->pixelsBytes),
            $attributeParser->parse($rawScreen->attributesBytes),
        );
    }
}
