<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Multiartist;

use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\PixelParser;

final readonly class MghScreenParser
{
    public function parse(MghData $data, int $width): MghParsedScreens
    {
        $attributeParser = new MghAttributeParser();
        $pixelParser = new PixelParser($width);

        return new MghParsedScreens(
            new ParsedScreen(
                $pixelParser->parse($data->firstPixelsBytes),
                $attributeParser->parse(
                    $data->mode,
                    $data->firstAttributesBytes,
                    $data->firstOuterAttributesBytes,
                    $width,
                ),
            ),
            new ParsedScreen(
                $pixelParser->parse($data->secondPixelsBytes),
                $attributeParser->parse(
                    $data->mode,
                    $data->secondAttributesBytes,
                    $data->secondOuterAttributesBytes,
                    $width,
                ),
            ),
        );
    }
}
