<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;

final readonly class GigascreenScreenParser
{
    public function parse(RawScreen $rawScreen, PluginGeometry $geometry): ParsedScreen
    {
        $attributes = (new AttributeParser($geometry->width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($geometry->width))->parse($rawScreen->pixelsBytes);

        return new ParsedScreen($pixelsData, $attributes);
    }
}
