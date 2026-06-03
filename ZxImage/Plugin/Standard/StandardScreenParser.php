<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Standard;

use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;

final readonly class StandardScreenParser
{
    public function parse(RawScreen $rawScreen, int $width): ParsedScreen
    {
        return new ParsedScreen(
            (new PixelParser($width))->parse($rawScreen->pixelsBytes),
            (new AttributeParser($width))->parse($rawScreen->attributesBytes),
            [],
            $rawScreen->borderBytes,
        );
    }
}
