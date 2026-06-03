<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;

final readonly class StandardParsedScreenParser
{
    public function parse(RawScreen $rawScreen, int $width): ParsedScreen
    {
        $attributes = (new AttributeParser($width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($width))->parse($rawScreen->pixelsBytes);

        return new ParsedScreen($pixelsData, $attributes);
    }

    public function parseWithLinearPixels(RawScreen $rawScreen, int $width): ParsedScreen
    {
        $linearMapper = static fn(int $y): int => $y;
        $attributes = (new AttributeParser($width))->parse($rawScreen->attributesBytes);
        $pixelsData = (new PixelParser($width))->parse($rawScreen->pixelsBytes, $linearMapper);

        return new ParsedScreen($pixelsData, $attributes);
    }

    public function parseWithZxAttributes(RawScreen $rawScreen, int $width): ParsedScreen
    {
        $zxyMapper = \Closure::fromCallable([$this, 'calculateZxY']);
        $attributes = (new AttributeParser($width))->parse($rawScreen->attributesBytes, $zxyMapper);
        $pixelsData = (new PixelParser($width))->parse($rawScreen->pixelsBytes);

        return new ParsedScreen($pixelsData, $attributes);
    }

    private function calculateZxY(int $y): int
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
