<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Gigascreen extends Standard
{
    /**
     * @var int|null
     */
    protected $strictFileSize = 13824;

    /**
     * @return string|null
     */
    public function convert()
    {
        $result = null;
        if ($bits = $this->loadBits()) {
            $parsedData1 = $this->parseScreen($bits[0]);
            $parsedData2 = $this->parseScreen($bits[1]);

            $gifImages = [];

            if ($this->gigascreenMode == 'flicker' || $this->gigascreenMode == 'interlace1' || $this->gigascreenMode == 'interlace2') {
                if (count($parsedData1['attributesData']['flashMap']) > 0 || count(
                        $parsedData2['attributesData']['flashMap']
                    ) > 0
                ) {
                    $image1 = $this->exportData($parsedData1, false);
                    $image2 = $this->exportData($parsedData2, false);
                    $image1f = $this->exportData($parsedData1, true);
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
                    $image1 = $this->exportData($parsedData1, false);
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
        $pixelsArray = [];
        $attributesArray = [];
        if ($this->makeHandle()) {
            $length = 0;
            $firstImage = false;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } else {
                    $attributesArray[] = $bin;
                }
                $length++;
                if ($length == 6912 && !$firstImage) {
                    $firstImage = [];
                    $firstImage['pixelsArray'] = $pixelsArray;
                    $firstImage['attributesArray'] = $attributesArray;

                    $pixelsArray = [];
                    $attributesArray = [];
                    $length = 0;
                }
            }
            $secondImage = [];
            $secondImage['pixelsArray'] = $pixelsArray;
            $secondImage['attributesArray'] = $attributesArray;
            $resultBits = [$firstImage, $secondImage];
            return $resultBits;
        }
        return null;
    }

    /**
     * @param array $parsedData1
     * @param array $parsedData2
     * @param bool $flashedImage
     * @return resource
     */
    protected function exportDataMerged(array $parsedData1, array $parsedData2, bool $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
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
                imagesetpixel($image, $x, $y, $color);
            }
        }
        $resultImage = $this->drawBorder($image, $parsedData1);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }
}
