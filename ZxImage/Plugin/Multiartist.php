<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Multiartist extends Gigascreen
{
    protected $mghMode = false;
    protected $borders = [];
    protected $mghMixedBorder = false;
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
            $parsedData1 = $this->parseScreen($bits[0]);
            $parsedData2 = $this->parseScreen($bits[1]);

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
                    $this->mghMixedBorder = true;
                    $image1 = $this->exportDataMerged($parsedData1, $parsedData2, false);
                    $gifImages[] = $this->getRightPaletteGif($image1);

                    $this->mghMixedBorder = true;
                    $image2 = $this->exportDataMerged($parsedData1, $parsedData2, true);
                    $gifImages[] = $this->getRightPaletteGif($image2);

                    $delays = [32, 32];

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                } else {
                    $this->mghMixedBorder = true;
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
            $length = 0;
            $header = '';
            while ($string = fgetc($this->handle)) {
                $header .= $string;
                $length++;

                if ($length == 256) {
                    break;
                }
            }
            $signature = substr($header, 0, 3);
            $version = ord(substr($header, 3, 1));
            if (is_numeric($this->border)) {
                $this->borders[0] = ord(substr($header, 5, 1));
                $this->borders[1] = ord(substr($header, 6, 1));
            } else {
                $this->borders[0] = false;
                $this->borders[1] = false;
            }
            $this->mghMode = ord(substr($header, 4, 1));

            $pixelsLength = 6144;
            $outerAttributesLength = 0;

            $attributesLength = 768;
            if ($this->mghMode == 1) {
                $this->attributeHeight = 1;
                $attributesLength = 3072;
                $outerAttributesLength = 384;
            }
            if ($this->mghMode == 2) {
                $this->attributeHeight = 2;
                $attributesLength = 3072;
            }
            if ($this->mghMode == 4) {
                $this->attributeHeight = 4;
                $attributesLength = 1536;
            }
            if ($this->mghMode == 8) {
                $this->attributeHeight = 8;
                $attributesLength = 768;
            }

            if ($signature == 'MGH' && $version == '1') {
                $firstImage = [];
                $secondImage = [];

                if ($this->mghMode == 1) {
                    $length = 0;

                    while ($bin = $this->read8BitString()) {
                        $bytesArray[] = $bin;

                        $length++;
                        if ($length == $pixelsLength) {
                            $firstImage['pixelsArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                        if ($length == $pixelsLength * 2) {
                            $secondImage['pixelsArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength) {
                            $firstImage['attributesArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength * 2) {
                            $secondImage['attributesArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength * 2 + $outerAttributesLength) {
                            $firstImage['outerAttributesArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength * 2 + $outerAttributesLength * 2) {
                            $secondImage['outerAttributesArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                    }
                } else {
                    $length = 0;

                    while ($bin = $this->read8BitString()) {
                        $bytesArray[] = $bin;

                        $length++;
                        if ($length == $pixelsLength) {
                            $firstImage['pixelsArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                        if ($length == $pixelsLength * 2) {
                            $secondImage['pixelsArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength) {
                            $firstImage['attributesArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength * 2) {
                            $secondImage['attributesArray'] = $bytesArray;
                            $bytesArray = [];
                        }
                    }
                }
                $resultBits = [$firstImage, $secondImage];
                return $resultBits;
            }
        }
        return null;
    }

    protected function parseScreen($data): array
    {
        if ($this->mghMode == 1) {
            $parsedData = [];
            $parsedData['attributesData'] = $this->parseMGH1Attributes(
                $data['attributesArray'],
                $data['outerAttributesArray']
            );
            $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        } else {
            $parsedData = [];
            $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
            $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        }

        return $parsedData;
    }

    protected function parseMGH1Attributes($attributesArray, $outerArray)
    {
        $x = 8;
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

            if ($x == 23) {
                $x = 8;
                $y++;
            } else {
                $x++;
            }
        }

        $x = 0;
        $y = 0;
        foreach ($outerArray as &$bits) {
            $ink = substr($bits, 1, 1) . substr($bits, 5);
            $paper = substr($bits, 1, 4);
            $flashStatus = substr($bits, 0, 1);

            for ($i = 0; $i < 8; $i++) {
                $attributesData['inkMap'][$y + $i][$x] = $ink;
                $attributesData['paperMap'][$y + $i][$x] = $paper;
                if ($flashStatus == '1') {
                    $attributesData['flashMap'][$y + $i][$x] = $flashStatus;
                }
            }
            if ($x == 7) {
                $x = 24;
            } elseif ($x == 31) {
                $x = 0;
                $y += 8;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }

    protected function drawBorder(
        $centerImage,
        array $parsedData1 = null,
        array $parsedData2 = null,
        bool $merged = false
    ) {
        if (is_numeric($this->borders[0]) && is_numeric($this->borders[1]) && $this->mghMixedBorder == true) {
            $resultImage = imagecreatetruecolor(320, 240);
            $code1 = sprintf('%04.0f', decbin($this->borders[0]));
            $code2 = sprintf('%04.0f', decbin($this->borders[1]));
            $color = $this->gigaColors[$code1 . $code2];
            imagefill($resultImage, 0, 0, $color);
            imagecopy($resultImage, $centerImage, 32, 24, 0, 0, $this->width, $this->height);
        } else {
            $resultImage = parent::drawBorder($centerImage, $parsedData1);
        }
        return $resultImage;
    }
}
