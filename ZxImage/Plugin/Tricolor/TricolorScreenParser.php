<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Tricolor;

use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\PixelParser;

final readonly class TricolorScreenParser
{
    private const int CELL_SIZE = 8;

    /**
     * @param list{list<int>, list<int>, list<int>} $screenPixelsBytes
     *
     * @return non-empty-list<ParsedScreen>
     */
    public function parse(array $screenPixelsBytes, int $width, int $height): array
    {
        $screens = [];
        /** @var list{list{int, int}, list{int, int}, list{int, int}} $screenColors */
        $screenColors = [
            [10, 0],
            [12, 0],
            [9, 0],
        ];

        foreach ($screenPixelsBytes as $screenIndex => $pixelsBytes) {
            [$inkKey, $paperKey] = $screenColors[$screenIndex];
            $attributes = $this->createAttributes($inkKey, $paperKey, $width, $height);
            $pixelsData = (new PixelParser($width))->parse($pixelsBytes);
            $screens[] = new ParsedScreen($pixelsData, $attributes);
        }

        return $screens;
    }

    private function createAttributes(int $inkKey, int $paperKey, int $width, int $height): AttributeMap
    {
        $rows = (int)($height / self::CELL_SIZE);
        $cols = (int)($width / self::CELL_SIZE);

        return new AttributeMap(
            array_fill(0, $rows, array_fill(0, $cols, $inkKey)),
            array_fill(0, $rows, array_fill(0, $cols, $paperKey)),
            [],
        );
    }
}
