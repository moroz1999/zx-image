<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Timexhr;

use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\PixelParser;

final readonly class TimexhrScreenParser
{
    /**
     * @param int[] $pixelsBytes
     */
    public function parse(array $pixelsBytes, int $attributeByte, int $width, int $height): ParsedScreen
    {
        $attributes = (new TimexhrAttributeBuilder())->build($attributeByte, $width, $height);
        $pixelsData = (new PixelParser($width))->parse($pixelsBytes);

        return new ParsedScreen($pixelsData, $attributes);
    }
}
