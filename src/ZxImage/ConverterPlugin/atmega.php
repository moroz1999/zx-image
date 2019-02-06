<?php

namespace ZxImage;

class ConverterPlugin_atmega extends ConverterPlugin
{
    const PIXELPAGESIZE = 8000;

    protected function loadBits()
    {
        if ($this->makeHandle()) {
            $pixelPageSize = self::PIXELPAGESIZE;
            $pixelsArray = $this->read8BitStrings($pixelPageSize * 4);
            $this->height = $this->readByte();
            $this->width = $this->readByte() * 8;
            $paletteArray = $this->read8BitStrings(16);

//            $paletteArray = [
//                '00000000', //black
//                '00000001', //blue
//                '00000010', //red
//                '00000011', //magenta
//                '00010000', //green
//                '00010001', //cyan
//                '00010010', //yellow
//                '00010011', //white
//                '00000000', //black2
//                '00100001', //blue
//                '01000010', //red
//                '01100011', //magenta
//                '10010000', //green
//                '10110001', //cyan
//                '11010010', //yellow
//                '11110011', //white
//            ];

            $resultBits = array(
                'pixelsArray'  => $pixelsArray,
                'paletteArray' => $paletteArray,
            );
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseAtmPalette($data['paletteArray']);
        return $parsedData;
    }

    protected function parsePixels($pixelsArray)
    {
        $x = 0;
        $y = 0;
        $length = 0;
        $block = 0;
        $pixelsData = array();
        foreach ($pixelsArray as &$bits) {
            $length++;
            $p1 = substr($bits, 1, 1) . substr($bits, 5, 3);
            $pixelsData[$y][$x * 2] = $p1;
            $p2 = substr($bits, 0, 1) . substr($bits, 2, 3);
            $pixelsData[$y][$x * 2 + 1] = $p2;

            $x = $x + 4;

            if ($x >= $this->width / 2) {
                $x = floor($length / self::PIXELPAGESIZE);
                if ($block != $x) {
                    $block = $x;
                    $y = 0;
                } else {
                    $y++;
                }
            }

        }
        return $pixelsData;

    }

    //grbG..RB
    //D0 – B (Blue)				(DSEL 0/1)
    //D1 – R (Red)				не используется
    //D2 – 1 - не используется		(FDC reset)
    //D3 – 1 - не используется		(FDC halt)
    //D4 – G (Green)			(Side 0/1)
    //D5 – b (Low Blue)			не используется
    //D6 – r (Low Red)			не используется
    //D7 – g (Low Green)			не используется

    protected function parseAtmPalette($paletteArray)
    {
        $paletteData = array();
        $levels = [
            0,
            0x55,
            0xaa,
            0xff
        ];
        foreach ($paletteArray as $clutItem) {
            $rValue = ((int)substr($clutItem, 6, 1) * 2 + (int)substr($clutItem, 1, 1));
            $gValue = ((int)substr($clutItem, 3, 1) * 2 + (int)substr($clutItem, 0, 1));
            $bValue = ((int)substr($clutItem, 7, 1) * 2 + (int)substr($clutItem, 2, 1));

            $r = $levels[$rValue];
            $g = $levels[$gValue];
            $b = $levels[$bValue];

            $redChannel = round(
                ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
            );
            $greenChannel = round(
                ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
            );
            $blueChannel = round(
                ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
            );

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;

            $paletteData[] = $RGB;
        }
        return $paletteData;
    }

    protected function exportData($parsedData, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                $color = $parsedData['colorsData'][bindec($pixel)];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }
}