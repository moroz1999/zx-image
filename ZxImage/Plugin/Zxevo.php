<?php

declare(strict_types=1);

namespace ZxImage\Plugin;


class Zxevo extends Plugin
{
    protected int $width = 320;
    protected int $height = 200;

    public function convert(): ?string
    {
        $result = null;
        if ($gdObject = $this->loadResource()) {
            $image = $this->adjustImage($gdObject);
            $result = $this->makePngFromGd($image);
        }
        return $result;
    }

    protected function loadBits(): ?array
    {
        return null;
    }

    protected function loadResource()
    {
        if (file_exists($this->sourceFilePath)) {
            if ($sizes = getimagesize($this->sourceFilePath)) {
                $this->width = $sizes[0];
                $this->height = $sizes[1];
            }

            $gdObject = imagecreatefrombmp($this->sourceFilePath);
            $colorsAmount = imagecolorstotal($gdObject);
            if ($colorsAmount <= 16 && $colorsAmount !== 0) {
                return $gdObject;
            }
        }
        return null;
    }

    protected function adjustImage($image)
    {
        $colorsAmount = imagecolorstotal($image);
        for ($i = 0; $i < $colorsAmount; $i++) {
            $color = imagecolorsforindex($image, $i);

            $color['red'] = (int)round($color['red'] / 85) * 85;
            $color['green'] = (int)round($color['green'] / 85) * 85;
            $color['blue'] = (int)round($color['blue'] / 85) * 85;

            imagecolorset($image, $i, $color['red'],  $color['green'],  $color['blue']);
        }

        $resultImage = $this->resizeImage($image);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function parseAttributes(array $attributesArray): array
    {
        $x = 0;
        $y = 0;
        $attributesData = ['inkMap' => [], 'paperMap' => []];
        foreach ($attributesArray as &$bits) {
            $ink = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 5, 3));
            $paper = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 2, 3)) + 8;

            $attributesData['inkMap'][$y][$x] = $ink;
            $attributesData['paperMap'][$y][$x] = $paper;

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }

    protected function parseScreen($data): array
    {
    }

    protected function exportData(array $parsedData, bool $flashedImage = false)
    {
    }
}
