<?php
namespace ZxImage;
if (!class_exists('\ZxImage\ConverterPlugin_bsc')) {
    include_once('bsc.php');
}

class ConverterPlugin_bmc4 extends ConverterPlugin_bsc
{
    protected $attributesLength = 1536;
    protected $attributeHeight = 4;
    protected $fileSize = 11904;

    protected function loadBits()
    {
        if ($resultBits = parent::loadBits()) {
            $attributesArray = array();
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
