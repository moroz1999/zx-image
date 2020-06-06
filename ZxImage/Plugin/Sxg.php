<?php

namespace ZxImage\Plugin;


class Sxg extends Plugin
{
    const FORMAT_256 = 2;
    const FORMAT_16 = 1;
    protected $sxgFormat = 2;

    protected $table = [
        0 => 0,
        1 => 10,
        2 => 21,
        3 => 31,
        4 => 42,
        5 => 53,
        6 => 63,
        7 => 74,
        8 => 85,
        9 => 95,
        10 => 106,
        11 => 117,
        12 => 127,
        13 => 138,
        14 => 149,
        15 => 159,
        16 => 170,
        17 => 181,
        18 => 191,
        19 => 202,
        20 => 213,
        21 => 223,
        22 => 234,
        23 => 245,
        24 => 255,
    ];

    protected function loadBits()
    {
        if ($this->makeHandle()) {
            $resultBits = [
                'pixelsArray' => [],
                'paletteArray' => [],
            ];
            $firstByte = $this->readByte();
            $signature = $this->readString(3);
            if ($firstByte == 127 && $signature == 'SXG') {
                $this->readByte(); //version
                $this->readByte(); //background
                $this->readByte(); //packed
                $this->sxgFormat = $this->readByte();
                $this->width = $this->readWord();
                $this->height = $this->readWord();
                $paletteShift = $this->readWord();
                $pixelsShift = $this->readWord();

                $this->readBytes($paletteShift - 2);
                $paletteArray = [];
                $paletteLength = ($pixelsShift - $paletteShift + 2) / 2;
                while ($paletteLength > 0) {
                    $paletteArray[] = $this->read16BitString();
                    $paletteLength--;
                }

                $pixelsArray = [];
                while (($word = $this->readByte()) !== false) {
                    $pixelsArray[] = $word;
                }

                $resultBits['pixelsArray'] = $pixelsArray;
                $resultBits['paletteArray'] = $paletteArray;
            }
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = [];
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseSxgPalette($data['paletteArray']);
        return $parsedData;
    }

    protected function parsePixels($pixelsArray)
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        if ($this->sxgFormat === self::FORMAT_16) {
            foreach ($pixelsArray as &$bits) {
                $bits = str_pad(decbin($bits), 8, '0', STR_PAD_LEFT);
                $pixelsData[$y][$x] = bindec(substr($bits, 0, 4));
                $x++;
                $pixelsData[$y][$x] = bindec(substr($bits, 4, 4));
                $x++;

                if ($x >= $this->width) {
                    $x = 0;
                    $y++;
                }
            }
        } elseif ($this->sxgFormat === self::FORMAT_256) {
            foreach ($pixelsArray as &$pixel) {
                $pixelsData[$y][$x] = $pixel;
                $x++;
                if ($x >= $this->width) {
                    $x = 0;
                    $y++;
                }
            }
        }
        return $pixelsData;

    }

    protected function parseSxgPalette($paletteArray)
    {
        $paletteData = [];
        foreach ($paletteArray as &$clutItem) {
            if (substr($clutItem, 0, 1) == '0') {
                $r = $this->table[bindec(substr($clutItem, 1, 5))];
                $g = $this->table[bindec(substr($clutItem, 6, 5))];
                $b = $this->table[bindec(substr($clutItem, 11, 5))];
            } else {
                $r = bindec(substr($clutItem, 1, 5)) << 3;
                $g = bindec(substr($clutItem, 6, 5)) << 3;
                $b = bindec(substr($clutItem, 11, 5)) << 3;
            }
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
                imagesetpixel($image, $x, $y, $parsedData['colorsData'][$pixel]);
            }
        }

        $resultImage = $this->resizeImage($image);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

}
