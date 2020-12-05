<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Sxg extends Plugin
{
    const FORMAT_256 = 2;
    const FORMAT_16 = 1;
    /**
     * @var int
     */
    protected $sxgFormat = 2;

    /**
     * @var mixed[]
     */
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

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        if ($this->makeHandle()) {
            $resultBits = [
                'pixelsArray' => [],
                'paletteArray' => [],
            ];
            $firstByte = $this->readByte();
            $signature = $this->readString(3);
            if ($firstByte === 127 && $signature === 'SXG') {
                $version = $this->readByte(); //version
                $background = $this->readByte(); //background
                $packed = $this->readByte(); //packed
                $this->sxgFormat = $this->readByte();
                $this->width = $this->readWord();
                $this->height = $this->readWord();
                $paletteShift = $this->readWord();
                $pixelsShift = $this->readWord();

                $this->readBytes($paletteShift - 2);

                $paletteLength = ($pixelsShift - $paletteShift + 2) / 2;
                $paletteArray = $this->read16BitStrings($paletteLength, false);

                $pixelsArray = [];
                while (($word = $this->readByte()) !== null) {
                    $pixelsArray[] = $word;
                }

                $resultBits['pixelsArray'] = $pixelsArray;
                $resultBits['paletteArray'] = $paletteArray;
            }
            return $resultBits;
        }
        return null;
    }

    protected function parseScreen($data): array
    {
        $parsedData = [];
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseSxgPalette($data['paletteArray']);
        return $parsedData;
    }

    protected function parsePixels(array $pixelsArray): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];
        if ($this->sxgFormat === self::FORMAT_16) {
            foreach ($pixelsArray as $bits) {
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
            foreach ($pixelsArray as $pixel) {
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
        foreach ($paletteArray as $clutItem) {
            if (substr($clutItem, 0, 1) == '0') {
                $color = bindec(substr($clutItem, 1, 5));
                $r = isset($this->table[$color]) ? $this->table[$color] : reset($this->table);
                $color = bindec(substr($clutItem, 6, 5));
                $g = isset($this->table[$color]) ? $this->table[$color] : reset($this->table);
                $color = bindec(substr($clutItem, 11, 5));
                $b = isset($this->table[$color]) ? $this->table[$color] : reset($this->table);
            } else {
                $r = bindec(substr($clutItem, 1, 5)) << 3;
                $g = bindec(substr($clutItem, 6, 5)) << 3;
                $b = bindec(substr($clutItem, 11, 5)) << 3;
            }

            $redChannel = $r;
            $greenChannel = $g;
            $blueChannel = $b;

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;

            $paletteData[] = $RGB;
        }
        return $paletteData;
    }

    protected function exportData(array $parsedData, bool $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => $row) {
            foreach ($row as $x => $pixel) {
                if (isset($parsedData['colorsData'][$pixel])) {
                    imagesetpixel($image, $x, $y, $parsedData['colorsData'][$pixel]);
                }
            }
        }

        $resultImage = $this->resizeImage($image);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

}
