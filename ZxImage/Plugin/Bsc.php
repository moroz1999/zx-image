<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Bsc extends Standard
{
    protected $attributesLength = 768;
    /**
     * @var int
     */
    protected $borderWidth = 64;
    /**
     * @var int
     */
    protected $borderHeight = 56;
    /**
     * @var int|null
     */
    protected $strictFileSize = 11136;

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        $pixelsArray = [];
        $attributesArray = [];
        $borderArray = [];
        if ($this->makeHandle()) {
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
            $resultBits = [
                'pixelsArray' => $pixelsArray,
                'attributesArray' => $attributesArray,
                'borderArray' => $borderArray,
            ];
            return $resultBits;
        }
        return null;
    }

    protected function parseScreen($data): array
    {
        $parsedData = [];
        $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['borderData'] = $data['borderArray'];
        return $parsedData;
    }

    protected function drawBorder(
        $centerImage,
        array $parsedData1 = null,
        array $parsedData2 = null,
        bool $merged = false
    ) {
        if (is_numeric($this->border)) {
            $resultImage = imagecreatetruecolor(
                $this->width + $this->borderWidth * 2,
                $this->height + $this->borderHeight * 2
            );

            $x = 0;
            $y = 0;

            foreach ($parsedData1['borderData'] as $byte) {
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
