<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Stellar extends Gigascreen
{
    /**
     * @var int|null
     */
    protected $strictFileSize = 3072;
    protected $atrWidth = 64;
    protected $atrHeight = 48;
    /**
     * @var int
     */
    protected $attributeHeight = 4;

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        $texture = [];
        $attributesArray = [[], []];
        if ($this->makeHandle()) {
            while (($bin = $this->read8BitString()) && ($bin2 = $this->read8BitString(
                )) && ($bin3 = $this->read8BitString()) && ($bin4 = $this->read8BitString())) {
                $attributesArray[0][] = $bin;
                $attributesArray[0][] = $bin2;
                $attributesArray[1][] = $bin3;
                $attributesArray[1][] = $bin4;
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

    protected function generatePixelsArray()
    {
        $pixelsArray = [];
        for ($x = 0; $x < $this->width * $this->height / 8; $x++) {
            $pixelsArray[] = '00001111';
        }
        return $pixelsArray;
    }
}
