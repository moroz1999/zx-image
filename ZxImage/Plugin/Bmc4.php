<?php

namespace ZxImage\Plugin;

class Bmc4 extends Bsc
{
    protected $attributesLength = 1536;
    protected int $attributeHeight = 4;
    protected ?int $fileSize = 11904;

    protected function loadBits(): ?array
    {
        if ($resultBits = parent::loadBits()) {
            $attributesArray = [];
            for ($j = 0; $j < 24; $j++) {
                for ($i = 0; $i < 32; $i++) {
                    $attributesArray[] = $resultBits['attributesArray'][$j * 32 + $i];
                }
                for ($i = 0; $i < 32; $i++) {
                    $attributesArray[] = $resultBits['attributesArray'][768 + $j * 32 + $i];
                }
            }
            $resultBits['attributesArray'] = $attributesArray;
        }
        return $resultBits;
    }

}
