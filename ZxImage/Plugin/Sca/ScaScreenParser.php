<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sca;

use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;

final readonly class ScaScreenParser
{
    /**
     * @param RawScreen[] $rawScreens
     * @return ParsedScreen[]
     */
    public function parseScreens(array $rawScreens, int $width): array
    {
        $parsedScreens = [];
        $pixelParser = new PixelParser($width);
        $attributeParser = new AttributeParser($width);

        foreach ($rawScreens as $rawScreen) {
            $parsedScreens[] = new ParsedScreen(
                $pixelParser->parse($rawScreen->pixelsBytes),
                $attributeParser->parse($rawScreen->attributesBytes),
            );
        }

        return $parsedScreens;
    }
}
