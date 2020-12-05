<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Lowresgs extends Gigascreen
{
    /**
     * @var int|null
     */
    protected $strictFileSize = 1628;

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        $texture = [];
        $attributesArray = [[], []];
        if ($this->makeHandle()) {
            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length >= 84 && $length < 92) {
                    $texture[] = $bin;
                } elseif ($length >= 92 && $length < 92 + 768) {
                    $attributesArray[0][] = $bin;
                } elseif ($length >= 92 + 768) {
                    $attributesArray[1][] = $bin;
                }
                $length++;
            }
            $pixelsArray = $this->generatePixelsArray($texture);
            $resultBits = [
                $resultBits = [
                    'pixelsArray' => $pixelsArray,
                    'attributesArray' => $attributesArray[0],
                ],
                [
                    'pixelsArray' => $pixelsArray,
                    'attributesArray' => $attributesArray[1],
                ],
            ];
            return $resultBits;
        }
        return null;
    }

    protected function generatePixelsArray($texture)
    {
        $pixelsArray = [];
        for ($third = 0; $third < 3; $third++) {
            $row = 0;
            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = $texture[$row];
                }
                $row++;
            }
        }
        return $pixelsArray;
    }
}
