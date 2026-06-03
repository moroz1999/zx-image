<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Monochrome;

use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Dto\RawScreen;
use ZxImage\Plugin\Standard\PixelParser;

final readonly class MonochromeScreenParser
{
    private const int INK_KEY = 15;
    private const int PAPER_KEY = 8;
    private const int CELL_SIZE = 8;

    public function parse(RawScreen $rawScreen, int $width, int $height): ParsedScreen
    {
        $pixelsData = (new PixelParser($width))->parse($rawScreen->pixelsBytes);
        $attributes = $this->createAttributes($width, $height);

        return new ParsedScreen($pixelsData, $attributes);
    }

    private function createAttributes(int $width, int $height): AttributeMap
    {
        $rows = (int)($height / self::CELL_SIZE);
        $cols = (int)($width / self::CELL_SIZE);

        return new AttributeMap(
            array_fill(0, $rows, array_fill(0, $cols, self::INK_KEY)),
            array_fill(0, $rows, array_fill(0, $cols, self::PAPER_KEY)),
            [],
        );
    }
}
