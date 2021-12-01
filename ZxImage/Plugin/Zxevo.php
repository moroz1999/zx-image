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
            return $gdObject;
        }
        return null;
    }

    protected function adjustImage($image)
    {
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $rgb = imagecolorat($image, $x, $y);

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $redChannel = (int)round(
                    ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
                );
                $greenChannel = (int)round(
                    ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
                );
                $blueChannel = (int)round(
                    ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
                );

                $color = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
                imagesetpixel($image, $x, $y, $color);
            }
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
