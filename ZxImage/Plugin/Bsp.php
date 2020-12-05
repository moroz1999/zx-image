<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Bsp extends Standard
{
    /**
     * @var int
     */
    protected $borderWidth = 64;
    /**
     * @var int
     */
    protected $borderHeightTop = 64;
    /**
     * @var int
     */
    protected $borderHeightBottom = 48;
    protected $hasGigaData;
    protected $hasBorderData;
    protected $borders = [];
    protected $author;
    protected $title;
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
        if ($bits = $this->loadBits()) {
            if ($this->hasGigaData) {
                $parsedData1 = $this->parseScreen($bits[0]);
                $parsedData2 = $this->parseScreen($bits[1]);
            } else {
                $parsedData1 = $this->parseScreen($bits);
                $parsedData2 = $parsedData1;
            }
            $gifImages = [];
            if ($this->gigascreenMode == 'flicker' || $this->gigascreenMode == 'interlace1' || $this->gigascreenMode == 'interlace2') {
                if (count($parsedData1['attributesData']['flashMap']) > 0 || count(
                        $parsedData2['attributesData']['flashMap']
                    ) > 0
                ) {
                    $this->border = $this->borders[0];
                    $image1 = $this->exportData($parsedData1, false);
                    $this->border = $this->borders[1];
                    $image2 = $this->exportData($parsedData2, false);
                    $this->border = $this->borders[0];
                    $image1f = $this->exportData($parsedData1, true);
                    $this->border = $this->borders[1];
                    $image2f = $this->exportData($parsedData2, true);

                    if ($this->gigascreenMode == 'interlace1') {
                        $this->interlaceMix($image1, $image2, 1);
                        $this->interlaceMix($image1f, $image2f, 1);
                    } elseif ($this->gigascreenMode == 'interlace2') {
                        $this->interlaceMix($image1, $image2, 2);
                        $this->interlaceMix($image1f, $image2f, 2);
                    }

                    $frame1 = $this->getRightPaletteGif($image1);
                    $frame2 = $this->getRightPaletteGif($image2);
                    $frame1f = $this->getRightPaletteGif($image1f);
                    $frame2f = $this->getRightPaletteGif($image2f);

                    $delays = [];
                    for ($i = 0; $i < 32; $i++) {
                        if ($i < 16) {
                            if ($i & 1) {
                                $gifImages[] = $frame1;
                            } else {
                                $gifImages[] = $frame2;
                            }
                        } else {
                            if ($i & 1) {
                                $gifImages[] = $frame1f;
                            } else {
                                $gifImages[] = $frame2f;
                            }
                        }
                        $delays[] = 2;
                    }

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                } else {
                    $this->border = $this->borders[0];
                    $image1 = $this->exportData($parsedData1, false);
                    $this->border = $this->borders[1];
                    $image2 = $this->exportData($parsedData2, false);

                    if ($this->gigascreenMode == 'interlace1') {
                        $this->interlaceMix($image1, $image2, 1);
                    } elseif ($this->gigascreenMode == 'interlace2') {
                        $this->interlaceMix($image1, $image2, 2);
                    }

                    $gifImages[] = $this->getRightPaletteGif($image1);
                    $gifImages[] = $this->getRightPaletteGif($image2);

                    $delays = [2, 2];

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                }
            } else {
                if (count($parsedData1['attributesData']['flashMap']) > 0 || count(
                        $parsedData2['attributesData']['flashMap']
                    ) > 0
                ) {
                    $image1 = $this->exportDataMerged($parsedData1, $parsedData2, false);
                    $gifImages[] = $this->getRightPaletteGif($image1);

                    $image2 = $this->exportDataMerged($parsedData1, $parsedData2, true);
                    $gifImages[] = $this->getRightPaletteGif($image2);

                    $delays = [32, 32];

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                } else {
                    $image = $this->exportDataMerged($parsedData1, $parsedData2, false);
                    $result = $this->makePngFromGd($image);
                }
            }
        }
        return $result;
    }

    /**
     * @return mixed[]|null
     */
    protected function loadBits()
    {
        if ($this->makeHandle()) {
            if ($this->readString(3) === 'bsp') {
                if (($configByte = $this->readByte()) !== null) {
                    $this->hasGigaData = (boolean)($configByte & 0b10000000);
                    $this->hasBorderData = (boolean)($configByte & 0b01000000);

                    //skip reserved byte
                    $this->readByte();

                    $borderColor = $this->readByte();
                    if (!$this->hasBorderData) {
                        $this->borders[0] = $colorCode = str_pad(
                            decbin($borderColor & 0b100000111),
                            4,
                            '0',
                            STR_PAD_LEFT
                        );
                        $this->borders[1] = $colorCode = str_pad(
                            decbin($borderColor & 0b100111000 >> 3),
                            4,
                            '0',
                            STR_PAD_LEFT
                        );
                    }
                    $this->title = trim($this->readString(32));
                    $this->author = trim($this->readString(32));

                    if ($this->hasBorderData) {
                        if ($this->hasGigaData) {
                            $secondBorderDataOffset = $this->readWord();

                            $firstImage = [
                                'pixelsArray' => $this->read8BitStrings(6144),
                                'attributesArray' => $this->read8BitStrings(768),
                            ];
                            $secondImage = [
                                'pixelsArray' => $this->read8BitStrings(6144),
                                'attributesArray' => $this->read8BitStrings(768),
                            ];
                            $firstImageBorderDataLength = $secondBorderDataOffset - 6912 * 2 - 70 - 2;
                            $secondBorderDataLength = $this->strictFileSize - $secondBorderDataOffset;
                            $firstImage['borderArray'] = $this->readBytes($firstImageBorderDataLength);
                            $secondImage['borderArray'] = $this->readBytes($secondBorderDataLength);

                            return [$firstImage, $secondImage];
                        } else {
                            $firstImage = [
                                'pixelsArray' => $this->read8BitStrings(6144),
                                'attributesArray' => $this->read8BitStrings(768),
                            ];
                            $firstImageBorderDataLength = $this->strictFileSize - 6912 - 70;
                            $firstImage['borderArray'] = $this->readBytes($firstImageBorderDataLength);
                            return $firstImage;
                        }
                    } else {
                        if ($this->hasGigaData) {
                            $firstImage = [
                                'pixelsArray' => $this->read8BitStrings(6144),
                                'attributesArray' => $this->read8BitStrings(768),
                            ];
                            $secondImage = [
                                'pixelsArray' => $this->read8BitStrings(6144),
                                'attributesArray' => $this->read8BitStrings(768),
                            ];
                            return [$firstImage, $secondImage];
                        } else {
                            $firstImage = [
                                'pixelsArray' => $this->read8BitStrings(6144),
                                'attributesArray' => $this->read8BitStrings(768),
                            ];
                            return $firstImage;
                        }
                    }
                }
            }
        }
        return null;
    }

    protected function parseScreen($data): array
    {
        $parsedData = [];
        $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        if (isset($data['borderArray'])) {
            $parsedData['borderData'] = $this->parseBorder($data['borderArray']);
        }
        return $parsedData;
    }

    protected function parseBorder($data)
    {
        $maxWidth = $this->width + $this->borderWidth * 2;
        $maxHeight = $this->height + $this->borderHeightTop + $this->borderHeightBottom;
        $borderData = [];
        $x = 0;
        $y = 0;
        $inCenter = false;
        while (($byte = array_shift($data)) !== null) {
            $colorCode = str_pad(decbin($byte & 0b00000111), 4, '0', STR_PAD_LEFT);
            $tacts = $byte >> 3;
            $line = 0;
            $untilEnd = false;
            if ($tacts == 0) {
                $untilEnd = true;
            } elseif ($tacts == 1) {
                $line = array_shift($data);
            } elseif ($tacts == 2) {
                $line = 12;
            } else {
                $line = $tacts + 13;
            }
            $line *= 2;
            while ($untilEnd || $line--) {
                $borderData[$y][$x] = $colorCode;
                $x++;

                if ($inCenter && $x == $this->borderWidth) {
                    $x = $this->borderWidth + $this->width;
                    $untilEnd = false;
                }
                if ($x == $maxWidth) {
                    $untilEnd = false;
                    $x = 0;
                    $y++;
                    if ($y < $this->borderHeightTop || $y >= $maxHeight - $this->borderHeightBottom) {
                        $inCenter = false;
                    } else {
                        $inCenter = true;
                    }
                }
            }
        }

        return $borderData;
    }

    protected function exportDataMerged($parsedData1, $parsedData2, $flashedImage = false)
    {
        $resultImage = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData1['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel1) {
                $mapPositionX = (int)($x / $this->attributeWidth);
                $mapPositionY = (int)($y / $this->attributeHeight);

                $pixel2 = $parsedData2['pixelsData'][$y][$x];
                if ($flashedImage && isset($parsedData1['attributesData']['flashMap'][$mapPositionY][$mapPositionX])) {
                    if ($pixel1 === '1') {
                        $ZXcolor = $parsedData1['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor = $parsedData1['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    }
                } else {
                    if ($pixel1 === '1') {
                        $ZXcolor = $parsedData1['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor = $parsedData1['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    }
                }

                if ($flashedImage && isset($parsedData2['attributesData']['flashMap'][$mapPositionY][$mapPositionX])) {
                    if ($pixel2 === '1') {
                        $ZXcolor .= $parsedData2['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor .= $parsedData2['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    }
                } else {
                    if ($pixel2 === '1') {
                        $ZXcolor .= $parsedData2['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor .= $parsedData2['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    }
                }

                $color = $this->gigaColors[$ZXcolor];
                imagesetpixel($resultImage, $x, $y, $color);
            }
        }
        $resultImage = $this->drawBorder($resultImage, $parsedData1, $parsedData2, true);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function drawBorder(
        $centerImage,
        array $parsedData1 = null,
        array $parsedData2 = null,
        bool $merged = false
    ) {
        if ($this->border !== null) {
            $totalWidth = $this->width + $this->borderWidth * 2;
            $totalHeight = $this->height + $this->borderHeightTop + $this->borderHeightBottom;
            $resultImage = imagecreatetruecolor(
                $totalWidth,
                $totalHeight
            );
            if ($merged) {
                for ($y = 0; $y < $totalHeight; $y++) {
                    for ($x = 0; $x < $totalWidth; $x++) {
                        if ($this->hasBorderData) {
                            if (isset($parsedData1['borderData'][$y][$x]) || isset($parsedData2['borderData'][$y][$x])) {
                                $colorCode = $parsedData1['borderData'][$y][$x] . $parsedData2['borderData'][$y][$x];
                                imagesetpixel($resultImage, $x, $y, $this->gigaColors[$colorCode]);
                            }
                        } else {
                            imagesetpixel(
                                $resultImage,
                                $x,
                                $y,
                                $this->gigaColors[$this->borders[0] . $this->borders[1]]
                            );
                        }
                    }
                }
            } else {
                for ($y = 0; $y < $totalHeight; $y++) {
                    for ($x = 0; $x < $totalWidth; $x++) {
                        if ($this->hasBorderData) {
                            if (isset($parsedData1['borderData'][$y][$x])) {
                                $colorCode = $parsedData1['borderData'][$y][$x];
                                imagesetpixel($resultImage, $x, $y, $this->colors[$colorCode]);
                            } else {
                                imagesetpixel($resultImage, $x, $y, $this->colors[$this->border]);
                            }
                        }
                    }
                }
            }

            imagecopy(
                $resultImage,
                $centerImage,
                $this->borderWidth,
                $this->borderHeightTop,
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