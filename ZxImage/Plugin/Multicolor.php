<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Multicolor extends Standard
{
    /**
     * @var int
     */
    protected $attributeHeight = 2;
    /**
     * @var int|null
     */
    protected $strictFileSize = 9216;

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
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
