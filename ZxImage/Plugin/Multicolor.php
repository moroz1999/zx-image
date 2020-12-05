<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Multicolor extends Standard
{
    protected int $attributeHeight = 2;
    protected ?int $strictFileSize = 9216;

    protected function loadBits(): ?array
    {
        $pixelsArray = [];
        $attributesArray = [];
        if ($this->makeHandle()) {
            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } else {
                    $attributesArray[] = $bin;
                }
                $length++;
            }
            return ['pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray];
        }
        return null;
    }
}
