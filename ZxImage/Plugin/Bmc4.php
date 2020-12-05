<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

class Bmc4 extends Bsc
{
    protected $attributesLength = 1536;
    /**
     * @var int
     */
    protected $attributeHeight = 4;
    /**
     * @var int|null
     */
    protected $strictFileSize = 11904;

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
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
