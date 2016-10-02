<?php
namespace ZxImage;

class ConverterPlugin_chrd extends ConverterPlugin_gigascreen
{
    protected $colorType;

    public function convert()
    {
        $result = false;
        $this->loadBits();
        if ($this->colorType == '9') {
            if ($bits = $this->loadBits()) {
                $parsedData = $this->parseScreen($bits);
                if (count($parsedData['attributesData']['flashMap']) > 0) {
                    $gifImages = array();

                    $image = $this->exportData($parsedData, false);
                    $gifImages[] = $this->getRightPaletteGif($image);

                    $image = $this->exportData($parsedData, true);
                    $gifImages[] = $this->getRightPaletteGif($image);

                    $delays = array(32, 32);
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

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        $pixelsArray2 = array();
        $attributesArray2 = array();
        if (file_exists($this->sourceFilePath)) {
            $this->handle = fopen($this->sourceFilePath, "rb");

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
                $resultBits = array(
                    'pixelsArray' => $pixelsArray
                );
            } elseif ($this->colorType == '9') {
                $resultBits = array(
                    'pixelsArray'     => $pixelsArray,
                    'attributesArray' => $attributesArray
                );
            } elseif ($this->colorType == '18') {
                $resultBits = array(
                    array('pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray),
                    array('pixelsArray' => $pixelsArray2, 'attributesArray' => $attributesArray2),
                );
            } else {
                $resultBits = array();
            }
            return $resultBits;
        }
        return false;
    }

    protected function parsePixels($pixelsArray)
    {
        $pixelsData = array();

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
