<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Attributes;

use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;

final readonly class AttributesScreenParser
{
    public function parse(RawScreen $rawScreen, int $width, int $height): ParsedScreen
    {
        $attributes = (new AttributeParser($width))->parse($rawScreen->attributesBytes);
        $pixelsData = $this->generateCheckerboardPixels($width, $height);

        return new ParsedScreen($pixelsData, $attributes);
    }

    private function generateCheckerboardPixels(int $width, int $height): array
    {
        $pixelsData = [];
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $pixelsData[$y][$x] = ($x + $y) % 2;
            }
        }

        return $pixelsData;
    }
}
