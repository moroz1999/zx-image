<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Chrd extends Gigascreen
{
    protected $colorType;
    /**
     * @var int|null
     */
    protected $strictFileSize;

    /**
     * @return string|null
     */
    public function convert()
    {
        $result = null;
        $this->loadBits();
        if ($this->colorType == '9') {
            if ($bits = $this->loadBits()) {
                $parsedData = $this->parseScreen($bits);
                if (count($parsedData['attributesData']['flashMap']) > 0) {
                    $gifImages = [];

                    $image = $this->exportData($parsedData, false);
                    $gifImages[] = $this->getRightPaletteGif($image);

                    $image = $this->exportData($parsedData, true);
                    $gifImages[] = $this->getRightPaletteGif($image);

                    $delays = [32, 32];
                    $result = $this->buildAnimatedGif($gifImages, $delays);
                } else {
                    $image = $this->exportData($parsedData, false);
                    $result = $this->makePngFromGd($image);
                }
            }
        } elseif ($this->colorType == '18') {
            $result = parent::convert();
        }
        return $result;
    }

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        $pixelsArray = [];
        $attributesArray = [];
        $pixelsArray2 = [];
        $attributesArray2 = [];
        if ($this->makeHandle()) {
            $length = 0;
            $signature = '';

            while ($bin = $this->readChar()) {
                $signature .= $bin;

                $length++;
                if ($length >= 4) {
                    break;
                }
            }
            if (strtolower($signature) == 'chr$') {
                $this->width = $this->readByte() * 8;
                $this->height = $this->readByte() * 8;
                $this->colorType = $this->readByte();

                for ($y = 0; $y < $this->height / 8; $y++) {
                    for ($x = 0; $x < $this->width / 8; $x++) {
                        if ($this->colorType == '8') {
                            for ($i = 0; $i < 8; $i++) {
                                $pixelsArray[] = $this->read8BitString();
                            }
                        }
                        if ($this->colorType == '9') {
                            for ($i = 0; $i < 8; $i++) {
                                $pixelsArray[] = $this->read8BitString();
                            }
                            $attributesArray[] = $this->read8BitString();
                        }
                        if ($this->colorType == '18') {
                            for ($i = 0; $i < 8; $i++) {
                                $pixelsArray[] = $this->read8BitString();
                            }
                            $attributesArray[] = $this->read8BitString();

                            for ($i = 0; $i < 8; $i++) {
                                $pixelsArray2[] = $this->read8BitString();
                            }
                            $attributesArray2[] = $this->read8BitString();
                        }
                    }
                }
            }

            if ($this->colorType == '8') {
                $resultBits = [
                    'pixelsArray' => $pixelsArray,
                ];
            } elseif ($this->colorType == '9') {
                $resultBits = [
                    'pixelsArray' => $pixelsArray,
                    'attributesArray' => $attributesArray,
                ];
            } elseif ($this->colorType == '18') {
                $resultBits = [
                    ['pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray],
                    ['pixelsArray' => $pixelsArray2, 'attributesArray' => $attributesArray2],
                ];
            } else {
                $resultBits = [];
            }
            return $resultBits;
        }
        return null;
    }

    protected function parsePixels(array $pixelsArray): array
    {
        $pixelsData = [];

        $x = 0;
        $y = 0;
        $yOffset = 0;
        foreach ($pixelsArray as &$bits) {
            $xOffset = 0;
            while ($xOffset < 8) {
                $bit = substr($bits, $xOffset, 1);

                $pixelsData[$y + $yOffset][$x + $xOffset] = $bit;

                $xOffset++;
            }
            $yOffset++;
            if ($yOffset >= 8) {
                $yOffset = 0;
                $x = $x + 8;
                if ($x >= $this->width) {
                    $x = 0;
                    $y = $y + 8;
                }
            }
        }
        return $pixelsData;
    }
}
