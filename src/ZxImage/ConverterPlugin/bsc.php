<?php
namespace ZxImage;

class ConverterPlugin_bsc extends ConverterPlugin_standard
{
    protected $attributesLength = 768;
    protected $borderWidth = 64;
    protected $borderHeight = 56;
    protected $fileSize = 11136;

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        $borderArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == $this->fileSize) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } elseif ($length < 6144 + $this->attributesLength) {
                    $attributesArray[] = $bin;
                } else {
                    $borderArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array(
                'pixelsArray'     => $pixelsArray,
                'attributesArray' => $attributesArray,
                'borderArray'     => $borderArray
            );
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['borderData'] = $data['borderArray'];
        return $parsedData;
    }

    protected function drawBorder($centerImage, $parsedData)
    {
        if (is_numeric($this->border)) {
            $resultImage = imagecreatetruecolor(
                $this->width + $this->borderWidth * 2,
                $this->height + $this->borderHeight * 2
            );

            $x = 0;
            $y = 0;

            foreach ($parsedData['borderData'] as $byte) {
                $left = "0" . substr($byte, 5, 3);

                $code = sprintf('%04.0f', $left);
                $color = $this->colors[$code];
                for ($i = 0; $i < 8; $i++) {
                    imagesetpixel($resultImage, $x + $i, $y, $color);
                }

                $x = $x + 8;
                $right = "0" . substr($byte, 2, 3);
                $code = sprintf('%04.0f', $right);
                $color = $this->colors[$code];
                for ($i = 0; $i < 8; $i++) {
                    imagesetpixel($resultImage, $x + $i, $y, $color);
                }

                $x = $x + 8;
                //skip central pixels for center of image
                if ($y >= ($this->borderHeight + 8) && $y < ($this->height + $this->borderHeight + 8) && $x == $this->borderWidth) {
                    $x = $x + $this->width;
                }

                if ($x >= $this->width + $this->borderWidth * 2) {
                    $x = 0;
                    $y++;
                }
            }

            imagecopy(
                $resultImage,
                $centerImage,
                $this->borderWidth,
                $this->borderHeight + 8,
                0,
                0,
                $this->width,
                $this->height
            );
        } else {
            $resultImage = $centerImage;
        }
        return $resultImage;
    }
}
