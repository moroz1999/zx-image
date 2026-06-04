<?php

declare(strict_types=1);

namespace ZxImage\Service\Image;

use GdImage;
use RuntimeException;

final readonly class ImageRotator
{
    public function rotate(GdImage $image, int $rotation): GdImage
    {
        if ($rotation === 0) {
            return $image;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $result = match ($rotation) {
            90, 270 => imagecreatetruecolor($height, $width),
            180 => imagecreatetruecolor($width, $height),
            default => null,
        };

        if ($result === null) {
            return $image;
        }
        if ($result === false) {
            throw new RuntimeException('Unable to create GD image');
        }

        for ($i = 0; $i < $width; $i++) {
            for ($j = 0; $j < $height; $j++) {
                $pixel = imagecolorat($image, $i, $j);
                if ($pixel === false) {
                    throw new RuntimeException('Unable to read GD image pixel');
                }
                match ($rotation) {
                    90 => imagesetpixel($result, ($height - 1) - $j, $i, $pixel),
                    180 => imagesetpixel($result, $width - $i, ($height - 1) - $j, $pixel),
                    270 => imagesetpixel($result, $j, $width - $i, $pixel),
                    default => null,
                };
            }
        }

        return $result;
    }
}
