<?php
namespace ZxImage;
if (!class_exists('\ZxImage\ConverterPlugin_standard')) {
    include_once('standard.php');
}

class ConverterPlugin_tricolor extends ConverterPlugin_standard
{
    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);

            if ($this->gigascreenMode == 'flicker') {
                $gifImages = array();
                $image = $this->exportData($parsedData[0], false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData[1], false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData[2], false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $delays = array(2, 2, 2);

                $result = $this->buildAnimatedGif($gifImages, $delays);
            } else {
                $resources = array();
                $resources[] = $this->exportData($parsedData[0], false);
                $resources[] = $this->exportData($parsedData[1], false);
                $resources[] = $this->exportData($parsedData[2], false);

                $result = $this->buildMixedPng($resources);
            }
        }
        return $result;
    }

    protected function buildMixedPng($resources)
    {
        $first = reset($resources);
        $width = imagesx($first);
        $height = imagesy($first);
        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $overall = 0;
                foreach ($resources as &$resource) {
                    $color = imagecolorat($resource, $x, $y);
                    $overall = $overall + $color;
                }
                imagesetpixel($image, $x, $y, $overall);
            }
        }
        $result = $this->makePngFromGd($image);
        return $result;
    }

    protected function loadBits()
    {
        $pixelsArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 6144 * 3) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            $image = 0;
            while ($bin = $this->read8BitString()) {
                if ($length == 6144) {
                    $length = 0;
                    $image++;
                    $pixelsArray[$image] = array();
                }
                $pixelsArray[$image][] = $bin;
                $length++;
            }
            $resultBits = array('pixelsArray' => $pixelsArray);
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData[0]['pixelsData'] = $this->parsePixels($data['pixelsArray'][0]);
        $parsedData[0]['attributesData'] = $this->generateAttributesArray('1010', '0000');
        $parsedData[1]['pixelsData'] = $this->parsePixels($data['pixelsArray'][1]);
        $parsedData[1]['attributesData'] = $this->generateAttributesArray('1100', '0000');
        $parsedData[2]['pixelsData'] = $this->parsePixels($data['pixelsArray'][2]);
        $parsedData[2]['attributesData'] = $this->generateAttributesArray('1001', '0000');
        return $parsedData;
    }

    protected function generateAttributesArray($inkColorCode, $paperColorCode)
    {
        $attributesData = array();
        for ($y = 0; $y < 24; $y++) {
            for ($x = 0; $x < 32; $x++) {
                $attributesData['inkMap'][$y][$x] = $inkColorCode;
                $attributesData['paperMap'][$y][$x] = $paperColorCode;
            }
        }
        $attributesData['flashMap'] = array();
        return $attributesData;
    }
}
