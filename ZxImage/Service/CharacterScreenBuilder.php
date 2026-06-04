<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Dto\RawScreen;

final readonly class CharacterScreenBuilder
{
    private const int CHARACTER_SIZE = 8;

    /**
     * @param int[] $tokens
     * @param callable(int): int[] $fontResolver
     */
    public function buildRawScreenFromTokens(
        array $tokens,
        callable $fontResolver,
        int $defaultAttribute,
        int $widthInCharacters,
    ): RawScreen {
        $pixelBytesByAddress = [];
        $attributeBytesByAddress = [];
        $characterX = 0;
        $characterY = 0;

        foreach ($tokens as $token) {
            $this->putCharacter(
                $pixelBytesByAddress,
                $attributeBytesByAddress,
                $characterX,
                $characterY,
                $fontResolver($token),
                $defaultAttribute,
                $widthInCharacters,
            );

            $characterX++;
            if ($characterX === $widthInCharacters) {
                $characterX = 0;
                $characterY++;
            }
        }

        ksort($pixelBytesByAddress);
        ksort($attributeBytesByAddress);

        return new RawScreen(array_values($pixelBytesByAddress), array_values($attributeBytesByAddress));
    }

    /**
     * @param array<int, array<int, list<int>>> $characterRows
     * @return array<int, array<int, int>>
     */
    public function buildPixelsFromCharacterRows(
        array $characterRows,
        int $widthInCharacters,
        int $heightInCharacters,
    ): array {
        $pixelsData = [];

        for ($characterY = 0; $characterY < $heightInCharacters; $characterY++) {
            for ($characterX = 0; $characterX < $widthInCharacters; $characterX++) {
                $characterBytes = $characterRows[$characterY][$characterX];
                for ($row = 0; $row < self::CHARACTER_SIZE; $row++) {
                    $byte = $characterBytes[$row];
                    $pixelY = $characterY * self::CHARACTER_SIZE + $row;
                    for ($bitPosition = 7; $bitPosition >= 0; $bitPosition--) {
                        $pixelX = $characterX * self::CHARACTER_SIZE + (7 - $bitPosition);
                        $pixelsData[$pixelY][$pixelX] = ($byte >> $bitPosition) & 1;
                    }
                }
            }
        }

        return $pixelsData;
    }

    /**
     * @param array<int, int> $pixelBytesByAddress
     * @param array<int, int> $attributeBytesByAddress
     * @param int[] $characterBytes
     */
    private function putCharacter(
        array &$pixelBytesByAddress,
        array &$attributeBytesByAddress,
        int $characterX,
        int $characterY,
        array $characterBytes,
        int $attribute,
        int $widthInCharacters,
    ): void {
        $attributeBytesByAddress[$characterY * $widthInCharacters + $characterX] = $attribute;

        for ($row = 0; $row < self::CHARACTER_SIZE; $row++) {
            $byte = $characterBytes[$row];
            $pixelBytesByAddress[$this->calculatePixelAddress($characterX, $characterY, $row, $widthInCharacters)] = $byte;
        }
    }

    private function calculatePixelAddress(
        int $characterX,
        int $characterY,
        int $row,
        int $widthInCharacters,
    ): int {
        $base = 0;
        if ($characterY > 15) {
            $base = $widthInCharacters * self::CHARACTER_SIZE * 16;
        } elseif ($characterY > 7) {
            $base = $widthInCharacters * self::CHARACTER_SIZE * 8;
        }

        return $base + $characterY * $widthInCharacters + $row * $widthInCharacters * self::CHARACTER_SIZE + $characterX;
    }
}
