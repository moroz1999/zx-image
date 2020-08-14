<?php

namespace ZxImage\Plugin;


class Mlt extends Standard
{
    protected int $attributeHeight = 1;
    protected ?int $fileSize = 12288;

    protected function parseAttributes($attributesArray)
    {
        $x = 0;
        $y = 0;
        $attributesData = ['inkMap' => [], 'paperMap' => [], 'flashMap' => []];
        foreach ($attributesArray as &$bits) {
            $ink = substr($bits, 1, 1) . substr($bits, 5);
            $paper = substr($bits, 1, 4);

            $attributesData['inkMap'][$y][$x] = $ink;
            $attributesData['paperMap'][$y][$x] = $paper;

            $flashStatus = substr($bits, 0, 1);
            if ($flashStatus == '1') {
                $attributesData['flashMap'][$y][$x] = $flashStatus;
            }

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }

}