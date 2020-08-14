<?php

namespace ZxImage\Plugin;


class Multicolor extends Standard
{
    protected int $attributeHeight = 2;
    protected ?int $fileSize = 9216;

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
            $resultBits = ['pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray];
            return $resultBits;
        }
        return null;
    }
}
