<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Chrd;

use ZxImage\Dto\AttributeMap;
use ZxImage\Dto\ChrdData;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Service\BitReader;
use ZxImage\Service\CharacterScreenBuilder;
use ZxImage\Service\PluginRuntime;

final readonly class ChrdLoader
{
    private const int COLOR_TYPE_STANDARD = 9;
    private const int COLOR_TYPE_GIGASCREEN = 18;

    public function load(PluginRuntime $runtime): ?ChrdData
    {
        $reader = $runtime->fileLoader->openSource($runtime->sourceFilePath, $runtime->sourceFileContents, null);
        if ($reader === null) {
            return null;
        }

        $signature = $reader->readString(4);
        if ($signature === null || strtolower($signature) !== 'chr$') {
            return null;
        }

        $widthInChars = $reader->readByte();
        $heightInChars = $reader->readByte();
        $colorType = $reader->readByte();

        if ($widthInChars === null || $heightInChars === null || $colorType === null) {
            return null;
        }

        if ($colorType !== self::COLOR_TYPE_STANDARD && $colorType !== self::COLOR_TYPE_GIGASCREEN) {
            return null;
        }

        $runtime->width = $widthInChars * 8;
        $runtime->height = $heightInChars * 8;

        $attributesArray1 = [];
        $attributesArray2 = [];
        /** @var array<int, array<int, list<int>>> $characterRows1 */
        $characterRows1 = [];
        /** @var array<int, array<int, list<int>>> $characterRows2 */
        $characterRows2 = [];

        for ($charY = 0; $charY < $heightInChars; $charY++) {
            for ($charX = 0; $charX < $widthInChars; $charX++) {
                $characterRows1[$charY][$charX] = $this->readCharacterBytes($reader);
                $attributesArray1[] = $reader->readByte() ?? 0;

                if ($colorType === self::COLOR_TYPE_GIGASCREEN) {
                    $characterRows2[$charY][$charX] = $this->readCharacterBytes($reader);
                    $attributesArray2[] = $reader->readByte() ?? 0;
                }
            }
        }

        $screenBuilder = new CharacterScreenBuilder();
        $attrParser = new AttributeParser($runtime->width);

        $screen1 = new ParsedScreen(
            $screenBuilder->buildPixelsFromCharacterRows($characterRows1, $widthInChars, $heightInChars),
            $attrParser->parse($attributesArray1),
        );

        $attributes2 = new AttributeMap([], [], []);
        $pixels2 = [];
        if ($colorType === self::COLOR_TYPE_GIGASCREEN) {
            $attributes2 = $attrParser->parse($attributesArray2);
            $pixels2 = $screenBuilder->buildPixelsFromCharacterRows($characterRows2, $widthInChars, $heightInChars);
        }
        $screen2 = new ParsedScreen($pixels2, $attributes2);

        return new ChrdData($colorType, $screen1, $screen2);
    }

    /**
     * @return list<int>
     */
    private function readCharacterBytes(BitReader $reader): array
    {
        $bytes = [];
        for ($i = 0; $i < 8; $i++) {
            $bytes[] = $reader->readByte() ?? 0;
        }
        return $bytes;
    }
}
