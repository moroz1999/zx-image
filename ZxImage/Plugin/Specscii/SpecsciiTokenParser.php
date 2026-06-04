<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Specscii;

use ZxImage\Dto\RawScreen;

final readonly class SpecsciiTokenParser
{
    private const int CHARS_PER_ROW = 32;

    /**
     * @param list<int> $tokens
     */
    public function parse(array $tokens): RawScreen
    {
        $pixelsArray = [];
        $attributesArray = [];
        $currentAttribute = 0;
        $command = null;
        $nextCommand = null;
        $attrX = 0;
        $attrY = 0;

        foreach ($tokens as $token) {
            if ($command === null) {
                $nextCommand = match ($token) {
                    SpecsciiChr::INK->value => SpecsciiChr::INK,
                    SpecsciiChr::PAPER->value => SpecsciiChr::PAPER,
                    SpecsciiChr::FLASH->value => SpecsciiChr::FLASH,
                    SpecsciiChr::BRIGHT->value => SpecsciiChr::BRIGHT,
                    default => $nextCommand,
                };
            }

            if ($command !== null) {
                $currentAttribute = match ($command) {
                    SpecsciiChr::INK => ($currentAttribute & ~0x07) | ($token & 0x07),
                    SpecsciiChr::PAPER => ($currentAttribute & ~0x38) | (($token & 0x07) << 3),
                    SpecsciiChr::FLASH => $token === 1 ? ($currentAttribute | 0x80) : ($currentAttribute & ~0x80),
                    SpecsciiChr::BRIGHT => $token === 1 ? ($currentAttribute | 0x40) : ($currentAttribute & ~0x40),
                };
                $command = null;
            } elseif ($nextCommand === null) {
                $charData = SpecsciiFontData::getChar($token - 32);
                $attributesArray[$attrY * self::CHARS_PER_ROW + $attrX] = $currentAttribute;
                foreach ($charData as $row => $pixels) {
                    $base = 0;
                    if ($attrY > 15) {
                        $base = self::CHARS_PER_ROW * 8 * 16;
                    } elseif ($attrY > 7) {
                        $base = self::CHARS_PER_ROW * 8 * 8;
                    }
                    $pixelsArray[$base + $attrY * self::CHARS_PER_ROW + $row * 256 + $attrX] = $pixels;
                }
                $attrX++;
                if ($attrX === self::CHARS_PER_ROW) {
                    $attrX = 0;
                    $attrY++;
                }
            } else {
                $command = $nextCommand;
                $nextCommand = null;
            }
        }

        ksort($pixelsArray);
        ksort($attributesArray);

        return new RawScreen(array_values($pixelsArray), array_values($attributesArray));
    }
}
